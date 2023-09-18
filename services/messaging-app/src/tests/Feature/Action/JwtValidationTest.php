<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Action;

use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface;

use function openssl_pkey_new;
use function sprintf;

use const OPENSSL_KEYTYPE_RSA;

/**
 * @group jwt-test
 */
class JwtValidationTest extends ActionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::instance($this->faker->dateTime));
    }

    public function testValidJwtAuthentication(): void
    {
        $response = $this->getResponse([
            'iat' => $this->getTimestamp(),
            'exp' => $this->getTimestamp(60),
            'mailboxUuid' => $this->faker->uuid,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testJwtAuthenticationMissingPayload(): void
    {
        $response = $this->getResponse([]);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonDataFromResponse([
            'status' => 'error',
            'message' => 'iat not set',
        ], $response);
    }

    public function testJwtAuthenticationEmptyIat(): void
    {
        $response = $this->getResponse(['iat' => null]);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonDataFromResponse([
            'status' => 'error',
            'message' => 'iat is not an integer',
        ], $response);
    }

    public function testJwtAuthenticationIatBeforeNow(): void
    {
        $iatAddSeconds = 10;
        $response = $this->getResponse(['iat' => $this->getTimestamp($iatAddSeconds)]);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonDataFromResponse([
            'status' => 'error',
            'message' => sprintf(
                'Cannot handle token prior to %s',
                CarbonImmutable::now()->addSeconds($iatAddSeconds)->format('Y-m-d\TH:i:s\+0000'),
            ),
        ], $response);
    }

    public function testJwtAuthenticationEmptyExp(): void
    {
        $response = $this->getResponse([
            'iat' => $this->getTimestamp(),
            'exp' => null,
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testJwtAuthenticationExpiredToken(): void
    {
        $response = $this->getResponse([
            'iat' => $this->getTimestamp(),
            'exp' => $this->getTimestamp(-10),
        ]);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonDataFromResponse([
            'status' => 'error',
            'message' => 'Expired token',
        ], $response);
    }

    public function testJwtAuthenticationMaxExpExceeded(): void
    {
        $response = $this->getResponse([
            'iat' => $this->getTimestamp(),
            'exp' => $this->getTimestamp(300),
        ]);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonDataFromResponse([
            'status' => 'error',
            'message' => 'max lifetime exceeded',
        ], $response);
    }

    public function testJwtAuthenticationInvalidSecret(): void
    {
        $response = $this->getResponse(
            [
                'iat' => $this->getTimestamp(),
                'exp' => $this->getTimestamp(60),
            ],
            'invalid',
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonDataFromResponse([
            'status' => 'error',
            'message' => 'Signature verification failed',
        ], $response);
    }

    public function testJwtAuthenticationHS512AlgorithmNotAllowed(): void
    {
        $response = $this->getResponse(
            [
                'iat' => $this->getTimestamp(),
                'exp' => $this->getTimestamp(60),
            ],
            'messagebox_jwt_secret',
            'HS512',
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testJwtAuthenticationHS384AlgorithmNotAllowed(): void
    {
        $response = $this->getResponse(
            [
                'iat' => $this->getTimestamp(),
                'exp' => $this->getTimestamp(60),
            ],
            'messagebox_jwt_secret',
            'HS384',
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testJwtAuthenticationEmptyKey(): void
    {
        $response = $this->getResponse(
            [
                'iat' => $this->getTimestamp(),
                'exp' => $this->getTimestamp(60),
            ],
            'messagebox_jwt_secret',
            'HS256',
            null,
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonDataFromResponse([
            'status' => 'error',
            'message' => '"kid" empty, unable to lookup correct key',
        ], $response);
    }

    public function testJwtAuthenticationInvalidKey(): void
    {
        $response = $this->getResponse(
            [
                'iat' => $this->getTimestamp(),
                'exp' => $this->getTimestamp(60),
            ],
            'messagebox_jwt_secret',
            'HS256',
            'invalid',
        );

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonDataFromResponse([
            'status' => 'error',
            'message' => '"kid" invalid, unable to lookup correct key',
        ], $response);
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
            'mailboxUuid' => $this->faker->uuid,
        ], $privateKey, 'RS256');

        $response = $this->getJson('/api/v1/messages', [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonDataFromResponse([
            'status' => 'error',
            'message' => 'Algorithm not allowed'
        ], $response);
    }

    private function getResponse(
        array $payload = [],
        string $key = 'messagebox_jwt_secret',
        string $algorithm = 'HS256',
        ?string $keyId = 'messagebox',
    ): ResponseInterface {
        JWT::$timestamp = CarbonImmutable::now()->timestamp;

        $token = JWT::encode($payload, $key, $algorithm, $keyId);

        return $this->getJson('/api/v1/messages', [
            'Authorization' => sprintf('Bearer %s', $token),
        ]);
    }
}
