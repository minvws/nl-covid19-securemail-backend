<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Enums\LoginType;
use App\Models\User;
use App\Repositories\Bridge\BridgeRequestException;
use App\Repositories\MessageRepository;
use App\Repositories\OtpCodeRepository;
use App\Services\AuthenticationService;
use Mockery\MockInterface;
use Ramsey\Uuid\Uuid;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use Tests\Feature\ControllerTestCase;

class OtpCodeControllerTest extends ControllerTestCase
{
    public function testPostOtpCode(): void
    {
        $aliasId = Uuid::uuid4()->toString();
        $message = $this->createMessage([
            'aliasId' => $aliasId
        ]);
        $pairingCode = $this->createPairingCode($message->uuid);
        $otpCode = $this->createOtpCode();
        $otpCodeNumber = $this->faker->randomNumber(6, true);

        $this->mock(
            OtpCodeRepository::class,
            function (MockInterface $mock) use ($message, $otpCode, $otpCodeNumber) {
                $mock->shouldReceive('getByMessageUuidAndOtpCode')
                    ->once()
                    ->with($message->uuid, $otpCodeNumber)
                    ->andReturn($otpCode->uuid);
            }
        );
        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message, $otpCode) {
            $mock->shouldReceive('getByUuidAndOtpCodeUuid')
                ->once()
                ->with($message->uuid, $otpCode->uuid)
                ->andReturn($message);
        });

        $response = $this
            ->withSession([
                AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $pairingCode,
                'otp_code' => $otpCode,
            ])
            ->postJson('/api/v1/otp', [
                'loginType' => LoginType::sms()->value,
                'otpCode' => (string) $otpCodeNumber,
            ]);

        $response->assertStatus(200);
        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_USER, new User(User::AUTH_OTP, $aliasId));
        $this->assertEquals([], $response->json());
    }

    public function testPostOtpCodeWithoutPairingCode(): void
    {
        $otpCode = $this->createOtpCode();
        $otpCodeNumber = $this->faker->randomNumber(6, true);

        $response = $this
            ->withSession([
                'otp_code' => $otpCode,
            ])
            ->postJson('/api/v1/otp', [
                'loginType' => LoginType::sms()->value,
                'otpCode' => (string) $otpCodeNumber,
            ]);

        $response->assertStatus(401);
        $this->assertEquals(['error' => 'no pairing-code found'], $response->json());
    }

    public function testPostOtpCodeButOtpCodeNotFound(): void
    {
        $message = $this->createMessage();
        $pairingCode = $this->createPairingCode($message->uuid);
        $otpCode = $this->createOtpCode();
        $otpCodeNumber = $this->faker->randomNumber(6, true);

        $this->mock(
            OtpCodeRepository::class,
            function (MockInterface $mock) use ($message, $otpCodeNumber) {
                $mock->shouldReceive('getByMessageUuidAndOtpCode')
                    ->once()
                    ->with($message->uuid, $otpCodeNumber)
                    ->andThrow(new RepositoryException('otp_code not found'));
            }
        );

        $response = $this
            ->withSession([
                AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $pairingCode,
                'otp_code' => $otpCode,
            ])
            ->postJson('/api/v1/otp', [
                'loginType' => LoginType::sms()->value,
                'otpCode' => (string) $otpCodeNumber,
            ]);

        $response->assertStatus(401);
        $this->assertEquals(['error' => 'otp_code not found'], $response->json());
    }

    public function testPostOtpCodeFoundOtpCodeButNotEqualToSession(): void
    {
        $message = $this->createMessage();
        $pairingCode = $this->createPairingCode($message->uuid);
        $otpCode = $this->createOtpCode();
        $otpCodeNumber = $this->faker->randomNumber(6, true);

        $this->mock(
            OtpCodeRepository::class,
            function (MockInterface $mock) use ($message, $otpCode, $otpCodeNumber) {
                $mock->shouldReceive('getByMessageUuidAndOtpCode')
                    ->once()
                    ->with($message->uuid, $otpCodeNumber)
                    ->andReturn($otpCode->uuid);
            }
        );

        $response = $this
            ->withSession([
                AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $pairingCode,
                'otp_code' => $this->createOtpCode(),
            ])
            ->postJson('/api/v1/otp', [
                'loginType' => LoginType::sms()->value,
                'otpCode' => (string) $otpCodeNumber,
            ]);

        $response->assertStatus(401);
        $this->assertEquals(['error' => 'otp_code not found'], $response->json());
    }

    public function testPostOtpCodeMessageNotFound(): void
    {
        $message = $this->createMessage();
        $pairingCode = $this->createPairingCode($message->uuid);
        $otpCode = $this->createOtpCode();
        $otpCodeNumber = $this->faker->randomNumber(6, true);

        $this->mock(
            OtpCodeRepository::class,
            function (MockInterface $mock) use ($message, $otpCode, $otpCodeNumber) {
                $mock->shouldReceive('getByMessageUuidAndOtpCode')
                    ->once()
                    ->with($message->uuid, $otpCodeNumber)
                    ->andReturn($otpCode->uuid);
            }
        );
        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message, $otpCode) {
            $mock->shouldReceive('getByUuidAndOtpCodeUuid')
                ->once()
                ->with($message->uuid, $otpCode->uuid)
                ->andThrow(new RepositoryException('message not found'));
        });

        $response = $this
            ->withSession([
                AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $pairingCode,
                'otp_code' => $otpCode,
            ])
            ->postJson('/api/v1/otp', [
                'loginType' => LoginType::sms()->value,
                'otpCode' => (string) $otpCodeNumber,
            ]);

        $response->assertStatus(401);
        $this->assertEquals(['error' => 'message not found'], $response->json());
    }

    public function testPostIncorrectPhone(): void
    {
        $messageUuid = $this->faker->uuid;

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($messageUuid) {
            $mock->shouldReceive('reportIncorrectPhone')
                ->once()
                ->with($messageUuid);
        });

        $pairingCode = $this->createPairingCode($messageUuid);
        $response = $this->withSession([AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $pairingCode])
            ->postJson('/api/v1/otp/incorrect-phone');

        $response->assertStatus(201);
        $this->assertEquals([], $response->json());
    }

    public function testPostIncorrectPhoneWithUnkownMessageUuid(): void
    {
        $messageUuid = $this->faker->uuid;

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($messageUuid) {
            $mock->shouldReceive('reportIncorrectPhone')
                ->once()
                ->with($messageUuid)
                ->andThrow(new BridgeRequestException('not found', 404));
        });

        $pairingCode = $this->createPairingCode($messageUuid);
        $response = $this->withSession([AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $pairingCode])
            ->postJson('/api/v1/otp/incorrect-phone');

        $response->assertStatus(404);
        $this->assertEquals(['error' => 'not found'], $response->json());
    }
}
