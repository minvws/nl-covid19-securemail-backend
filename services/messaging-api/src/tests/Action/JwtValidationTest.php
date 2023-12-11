<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\Action;

use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use MinVWS\MessagingApi\Repository\MessageReadRepository;

use function array_merge;
use function openssl_pkey_new;
use function sprintf;

use const OPENSSL_KEYTYPE_RSA;

class JwtValidationTest extends ActionTestCase
{
    /**
     * @dataProvider jwtAuthenticationDataProvider
     */
    public function testJwtAuthentication(
        array $payload,
        string $secret,
        string $algorithm,
        ?string $key,
        int $expectedResponseStatusCode,
        string $expectedErrorMessage = null,
    ): void {
        $testNow = '2020-01-01';
        CarbonImmutable::setTestNow($testNow);
        JWT::$timestamp = CarbonImmutable::now()->timestamp;

        $token = JWT::encode($payload, $secret, $algorithm, $key);

        $messageReadRepository = $this->mock(MessageReadRepository::class);
        $messageReadRepository->method('countStatusUpdates')
            ->willReturn(0);
        $messageReadRepository->method('getStatusUpdates')
            ->willReturn([]);

        $response = $this->get('/api/v1/messages/statusupdates', [
            'since' => CarbonImmutable::now()->format('c'),
        ], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals($expectedResponseStatusCode, $response->getStatusCode());
        if ($expectedErrorMessage !== null) {
            $this->assertJsonDataFromResponse([
                'status' => 'error',
                'message' => $expectedErrorMessage
            ], $response);
        }
    }

    public function jwtAuthenticationDataProvider(): array
    {
        $testNow = '2020-01-01';
        CarbonImmutable::setTestNow($testNow);
        JWT::$timestamp = CarbonImmutable::now()->timestamp;

        $payload = [
            'iat' => $this->getTimestamp(),
            'exp' => $this->getTimestamp(60),
        ];

        $messagingApiJwtSecret = 'jwt-secret'; // set in phpunit.xml
        $platformIdentifier = 'platform-identifier'; // set in phpunit.xml
        $algorithm = 'HS256';

        $dataSets = [
            'valid' => [$payload, $messagingApiJwtSecret, $algorithm, $platformIdentifier, 200],
            'missing payload' => [[], $messagingApiJwtSecret, $algorithm, $platformIdentifier, 401, 'iat not set'],
            'missing iat' => [
                array_merge($payload, ['iat' => null]),
                $messagingApiJwtSecret,
                $algorithm,
                $platformIdentifier,
                401,
                'iat is not an integer',
            ],
            'iat before now' => [
                array_merge($payload, ['iat' => $this->getTimestamp(10)]),
                $messagingApiJwtSecret,
                $algorithm,
                $platformIdentifier,
                401,
                'Cannot handle token prior to 2020-01-01T00:00:10+0000',
            ],
            'missing exp' => [
                array_merge($payload, ['exp' => null]),
                $messagingApiJwtSecret,
                $algorithm,
                $platformIdentifier,
                401,
            ],
            'expired token' => [
                array_merge($payload, ['exp' => $this->getTimestamp(-10)]),
                $messagingApiJwtSecret,
                $algorithm,
                $platformIdentifier,
                401,
                'Expired token'
            ],
            'max exp exceeded' => [
                array_merge($payload, ['exp' => $this->getTimestamp(300)]),
                $messagingApiJwtSecret,
                $algorithm,
                $platformIdentifier,
                401,
                'max lifetime exceeded'
            ],
            'invalid secret' => [
                $payload,
                'foo',
                $algorithm,
                $platformIdentifier,
                401,
                'Signature verification failed'
            ],
            'HS512 alg' => [$payload, $messagingApiJwtSecret, 'HS512', $platformIdentifier, 401],
            'HS384 alg' => [$payload, $messagingApiJwtSecret, 'HS384', $platformIdentifier, 401],
            'missing key (kid)' => [
                $payload,
                $messagingApiJwtSecret,
                $algorithm,
                null,
                401,
                '"kid" empty, unable to lookup correct key',
            ],
            'invalid key (kid)' => [
                $payload,
                $messagingApiJwtSecret,
                $algorithm,
                'foo',
                401,
                '"kid" invalid, unable to lookup correct key',
            ],
        ];

        return $dataSets;
    }

    public function testJwtAuthenticationRS256AlgorithmNotAllowed(): void
    {
        $privateKey = openssl_pkey_new([
            'digest_alg' => 'rsa256',
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $token = JWT::encode([
            'iat' => $this->getTimestamp(),
            'exp' => $this->getTimestamp(60),
        ], $privateKey, 'RS256');

        $response = $this->get('/api/v1/messages/statusupdates', [
            'since' => CarbonImmutable::now()->format('c'),
        ], [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonDataFromResponse([
            'status' => 'error',
            'message' => 'Algorithm not allowed'
        ], $response);
    }
}
