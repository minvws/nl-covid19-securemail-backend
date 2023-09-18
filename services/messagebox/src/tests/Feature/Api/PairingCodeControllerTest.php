<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Exceptions\PairingCodeInvalidException;
use App\Models\Enums\Error;
use App\Models\Enums\LoginType;
use App\Models\OtpCode;
use App\Models\PairingCode;
use App\Models\User;
use App\Repositories\Bridge\BridgeRequestException;
use App\Repositories\MessageRepository;
use App\Repositories\PairingCodeRepository;
use App\Services\AuthenticationService;
use App\Services\OtpCodeService;
use Carbon\CarbonImmutable;
use Mockery\MockInterface;
use Tests\Feature\ControllerTestCase;

use function strval;

class PairingCodeControllerTest extends ControllerTestCase
{
    public function testPostPairingCode(): void
    {
        $emailAddress = $this->faker->safeEmail;
        $pairingCode = $this->faker->regexify('[A-Za-z0-9]{6}');

        $this->mock(PairingCodeRepository::class, function (MockInterface $mock) use ($emailAddress, $pairingCode) {
            $mock->shouldReceive('getByEmailAddressAndPairingCode')
                ->once()
                ->with($emailAddress, $pairingCode)
                ->andReturn(new PairingCode(
                    $this->faker->uuid,
                    $this->faker->uuid,
                    $emailAddress,
                    'name',
                    CarbonImmutable::tomorrow(),
                ));
        });

        $response = $this->postJson('/api/v1/pairing_code', [
            'emailAddress' => $emailAddress,
            'pairingCode' => $pairingCode,
        ]);

        $response->assertStatus(200);
        $this->assertEquals([], $response->json());
    }

    /**
     * @group api-pairing
     */
    public function testPostPairingCodeWithActiveDigidSession(): void
    {
        $user = new User(User::AUTH_DIGID, $this->faker->uuid);
        $message = $this->createMessage();

        $pairingCodeString = $this->faker->regexify('[0-9]{6}');
        $emailAddress = $this->faker->email;
        $pairingCode = new PairingCode(
            $this->faker->uuid,
            $message->uuid,
            $emailAddress,
            $this->faker->name,
            CarbonImmutable::tomorrow(),
        );

        $activePairingCode = new PairingCode(
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->email,
            $this->faker->name,
            CarbonImmutable::now()->sub('2 minutes'),
        );

        $this->mock(PairingCodeRepository::class, function (MockInterface $mock) use ($emailAddress, $pairingCodeString, $pairingCode) {
            $mock->shouldReceive('getByEmailAddressAndPairingCode')
                ->once()
                ->with($emailAddress, $pairingCodeString)
                ->andReturn($pairingCode);
        });

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message, $user) {
            $mock->shouldReceive('getByUuidAndPseudoBsn')
                ->once()
                ->with($message->uuid, $user->getAuthIdentifier())
                ->andReturn($message);
        });

        $response = $this->be($user)
            ->withSession([
                AuthenticationService::SESSION_AUTHENTICATION_USER => $user,
                AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $activePairingCode,
            ])->postJson('/api/v1/pairing_code', [
                'emailAddress' => $emailAddress,
                'pairingCode' => $pairingCodeString,
            ])
        ;

        $response->assertStatus(200);
        $this->assertEquals([], $response->json());
        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_USER, $user);
        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE, $pairingCode);
    }
    /**
     * @group api-pairing
     */
    public function testPostPairingCodeWithActiveOtpSession(): void
    {
        $user = new User(strval($this->faker->randomElement([User::AUTH_OTP])), $this->faker->uuid);
        $otpCode = new OtpCode(
            $this->faker->uuid,
            LoginType::sms(),
            $this->faker->phoneNumber,
            CarbonImmutable::tomorrow()
        );
        $message = $this->createMessage();

        $pairingCodeString = $this->faker->regexify('[0-9]{6}');
        $emailAddress = $this->faker->email;
        $pairingCode = new PairingCode(
            $this->faker->uuid,
            $message->uuid,
            $emailAddress,
            $this->faker->name,
            CarbonImmutable::tomorrow(),
        );

        $activePairingCode = new PairingCode(
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->email,
            $this->faker->name,
            CarbonImmutable::now()->sub('2 minutes'),
        );

        $this->mock(PairingCodeRepository::class, function (MockInterface $mock) use ($emailAddress, $pairingCodeString, $pairingCode) {
            $mock->shouldReceive('getByEmailAddressAndPairingCode')
                ->once()
                ->with($emailAddress, $pairingCodeString)
                ->andReturn($pairingCode);
        });

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message, $otpCode) {
            $mock->shouldReceive('getByUuidAndOtpCodeUuid')
                ->once()
                ->with($message->uuid, $otpCode->uuid)
                ->andReturn($message);
        });

        $response = $this->be($user)
            ->withSession([
                AuthenticationService::SESSION_AUTHENTICATION_USER => $user,
                AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $activePairingCode,
                OtpCodeService::SESSION_KEY => $otpCode
            ])->postJson('/api/v1/pairing_code', [
                'emailAddress' => $emailAddress,
                'pairingCode' => $pairingCodeString,
            ])
        ;

        $response->assertStatus(200);
        $this->assertEquals([], $response->json());
        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_USER, $user);
        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE, $pairingCode);
    }

    public function testPostPairingCodeExpired(): void
    {
        $emailAddress = $this->faker->safeEmail;
        $pairingCode = $this->faker->regexify('[A-Za-z0-9]{6}');
        $pairingCodeUuid = $this->faker->uuid;

        $this->mock(
            PairingCodeRepository::class,
            function (MockInterface $mock) use ($emailAddress, $pairingCodeUuid, $pairingCode) {
                $mock->shouldReceive('getByEmailAddressAndPairingCode')
                    ->once()
                    ->with($emailAddress, $pairingCode)
                    ->andReturn(new PairingCode(
                        $pairingCodeUuid,
                        $this->faker->uuid,
                        $emailAddress,
                        $this->faker->name,
                        CarbonImmutable::yesterday(),
                    ));
            }
        );

        $response = $this->postJson('/api/v1/pairing_code', [
            'emailAddress' => $emailAddress,
            'pairingCode' => $pairingCode,
        ]);

        $response->assertStatus(410);

        $expectedResponse = [
            'error' => Error::pairingCodeExpired()->value,
            'emailAddress' => $emailAddress,
            'pairingCodeUuid' => $pairingCodeUuid,
        ];
        $this->assertEquals($expectedResponse, $response->json());
    }

    public function testPostPairingCodeException(): void
    {
        $emailAddress = $this->faker->safeEmail;

        $this->mock(PairingCodeRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('getByEmailAddressAndPairingCode')
                ->once()
                ->andThrow(new PairingCodeInvalidException($this->faker->sentence, 500));
        });

        $response = $this->postJson('/api/v1/pairing_code', [
            'emailAddress' => $emailAddress,
            'pairingCode' => $this->faker->regexify('[A-Za-z0-9]{6}'),
        ]);

        $response->assertStatus(401);
        $expectedResponse = [
            'error' => Error::pairingCodeInvalid(),
            'emailAddress' => $emailAddress,
            'pairingCodeUuid' => null,
        ];
        $this->assertEquals($expectedResponse, $response->json());
    }

    /**
     * @dataProvider postPairingCodeValidationDataProvider
     */
    public function testPostPairingCodeValidation(array $postData): void
    {
        $response = $this->postJson('/api/v1/pairing_code', $postData);

        $response->assertStatus(422);
    }

    public function postPairingCodeValidationDataProvider(): array
    {
        return [
            'too short pairingCode' => [[
                'pairingCode' => 'abc',
                'emailAddress' => 'foo@bar.com',
            ]],
            'too long pairingCode' => [[
                'pairingCode' => 'abcd1234',
                'emailAddress' => 'foo@bar.com',
            ]],
            'invalid email' => [[
                'pairingCode' => 'foofoo',
                'emailAddress' => 'foo@bar',
            ]],
        ];
    }

    public function testPostPairingCodeRenew(): void
    {
        $pairingCodeUuid = $this->faker->uuid;

        $this->mock(PairingCodeRepository::class, function (MockInterface $mock) use ($pairingCodeUuid): void {
            $mock->shouldReceive('renew')
                ->once()
                ->with($pairingCodeUuid);
        });

        $response = $this->postJson('/api/v1/pairing_code/renew', [
            'pairingCodeUuid' => $pairingCodeUuid,
        ]);

        $response->assertStatus(201);
        $this->assertEquals([], $response->json());
    }

    public function testPostPairingCodeRenewError(): void
    {
        $pairingCodeUuid = $this->faker->uuid;
        $errorMessage = $this->faker->sentence;

        $this->mock(
            PairingCodeRepository::class,
            function (MockInterface $mock) use ($errorMessage, $pairingCodeUuid): void {
                $mock->shouldReceive('renew')
                    ->once()
                    ->with($pairingCodeUuid)
                    ->andThrow(new BridgeRequestException($errorMessage));
            }
        );

        $response = $this->postJson('/api/v1/pairing_code/renew', [
            'pairingCodeUuid' => $pairingCodeUuid,
        ]);

        $response->assertStatus(500);
        $this->assertEquals(['error' => $errorMessage], $response->json());
    }

    public function testPostPairingCodeRenewValidation(): void
    {
        $response = $this->postJson('/api/v1/pairing_code/renew');

        $response->assertStatus(422);
    }
}
