<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Bridge;

use App\Models\Enums\LoginType;
use App\Repositories\Bridge\BridgeOtpCodeRepository;
use App\Repositories\Bridge\BridgeRequestException;
use MinVWS\Bridge\Client\Client;
use MinVWS\Bridge\Client\Response;
use Mockery\MockInterface;
use Psr\Log\NullLogger;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use Tests\TestCase;

use function array_merge;
use function json_encode;

class BridgeOtpCodeRepositoryTest extends TestCase
{
    public function testGetByMessageUuidAndOtpCode(): void
    {
        $messageUuid = $this->faker->uuid;
        $otpCodeUuid = $this->faker->uuid;
        $otpCode = $this->faker->lexify('??????');

        /** @var Client|MockInterface $bridgeClient */
        $bridgeClient = $this->mock(Client::class, function (MockInterface $mock) use ($otpCodeUuid): void {
                $mock->shouldReceive('request')
                    ->once()
                    ->andReturn($this->generateResponse(['uuid' => $otpCodeUuid]));
        });

        $bridgeMessageRepository = new BridgeOtpCodeRepository($bridgeClient, new NullLogger());
        $response = $bridgeMessageRepository->getByMessageUuidAndOtpCode($messageUuid, $otpCode);

        $this->assertEquals($response, $otpCodeUuid);
    }

    public function testGetByMessageUuidAndOtpCodeThrowsException(): void
    {
        /** @var Client|MockInterface $bridgeClient */
        $bridgeClient = $this->mock(Client::class, function (MockInterface $mock): void {
            $mock->shouldReceive('request')
                ->once()
                ->andThrow(new BridgeRequestException('some error'));
        });

        $bridgeMessageRepository = new BridgeOtpCodeRepository($bridgeClient, new NullLogger());
        $this->expectException(RepositoryException::class);
        $bridgeMessageRepository->getByMessageUuidAndOtpCode(
            $this->faker->uuid,
            $this->faker->lexify('??????'),
        );
    }

    public function testRequestByTypeAndMessageUuid(): void
    {
        /** @var Client|MockInterface $bridgeClient */
        $bridgeClient = $this->mock(Client::class, function (MockInterface $mock): void {
            $mock->shouldReceive('request')
                ->once()
                ->andReturn($this->generateResponse());
        });

        $bridgeMessageRepository = new BridgeOtpCodeRepository($bridgeClient, new NullLogger());
        $bridgeMessageRepository->requestByTypeAndMessageUuid(
            $this->faker->randomElement(LoginType::all()),
            $this->faker->uuid,
        );
    }

    public function testRequestByTypeAndMessageUuidThrowsException(): void
    {
        /** @var Client|MockInterface $bridgeClient */
        $bridgeClient = $this->mock(Client::class, function (MockInterface $mock): void {
            $mock->shouldReceive('request')
                ->once()
                ->andThrow(new BridgeRequestException('some error'));
        });

        $bridgeMessageRepository = new BridgeOtpCodeRepository($bridgeClient, new NullLogger());
        $this->expectException(RepositoryException::class);
        $bridgeMessageRepository->requestByTypeAndMessageUuid(
            $this->faker->randomElement(LoginType::all()),
            $this->faker->uuid,
        );
    }

    private function generateResponse(array $responseData = []): Response
    {
        $fakerResponseData = [
            'uuid' => $this->faker->uuid,
            'type' => $this->faker->randomElement(LoginType::all()),
            'phoneNumber' => $this->faker->regexify('*******[0-9]{3}'),
            'validUntil' => $this->faker->dateTimeBetween('-15 minutes')->format('c'),
        ];

        return new Response(json_encode(array_merge($fakerResponseData, $responseData)));
    }
}
