<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Exceptions\PairingCodeInvalidException;
use App\Models\Enums\Error;
use App\Models\Enums\LoginType;
use App\Models\OtpCode;
use App\Models\PairingCode;
use App\Models\User;
use App\Repositories\MessageRepository;
use App\Services\AuthenticationService;
use App\Services\MessageService;
use App\Services\OtpCodeService;
use App\Services\PairingCodeService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Mockery\MockInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use Tests\Feature\ControllerTestCase;

use function base64_encode;
use function json_encode;
use function random_bytes;
use function sodium_crypto_box;
use function sodium_crypto_box_keypair;
use function sodium_crypto_box_keypair_from_secretkey_and_publickey;
use function sodium_crypto_box_publickey;
use function sodium_crypto_box_secretkey;
use function sprintf;
use function urlencode;

use const SODIUM_CRYPTO_BOX_NONCEBYTES;

class PairingCodeControllerTest extends ControllerTestCase
{
    private string $messagingAppPrivateKey;
    private string $messageBoxPublicKey;

    protected function setUp(): void
    {
        parent::setUp();

        $messagingAppKeypair = sodium_crypto_box_keypair();
        $this->messagingAppPrivateKey = sodium_crypto_box_secretkey($messagingAppKeypair);
        $messagingAppPublicKey = sodium_crypto_box_publickey($messagingAppKeypair);

        $messageBoxKeypair = sodium_crypto_box_keypair();
        $messageBoxPrivateKey = sodium_crypto_box_secretkey($messageBoxKeypair);
        $this->messageBoxPublicKey = sodium_crypto_box_publickey($messageBoxKeypair);

        $this->config->set('encryption.pairing_code.private_key', base64_encode($messageBoxPrivateKey));
        $this->config->set('encryption.pairing_code.public_key', base64_encode($messagingAppPublicKey));
    }

    public function testLoginByCode(): void
    {
        $pairingCodeUuid = 'foo';
        $pairingCode = new PairingCode(
            $pairingCodeUuid,
            'messageUuid',
            'emailAddress',
            'name',
            CarbonImmutable::tomorrow(),
        );

        $this->mock(PairingCodeService::class, function (MockInterface $mock) use ($pairingCodeUuid, $pairingCode) {
            $mock->shouldReceive('getByUuid')
                ->once()
                ->with($pairingCodeUuid)
                ->andReturn($pairingCode);
        });

        $code = urlencode(urlencode($this->generateCode($pairingCodeUuid)));
        $response = $this->get(sprintf('inloggen/code/%s', $code));

        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE, $pairingCode);
        $response->assertRedirect('/auth/login');
    }

    public function testLoginByCodeDecryptFails(): void
    {
        $response = $this->get('inloggen/code/invalid');

        $response->assertRedirect('/error/pairing_code_invalid');
        $response->assertSessionMissing(AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE);
    }

    public function testLoginByCodePairingCodeNotFound(): void
    {
        $this->mock(PairingCodeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getByUuid')
                ->once()
                ->andThrow(PairingCodeInvalidException::class);
        });

        $code = urlencode(urlencode($this->generateCode('foo')));
        $response = $this->get(sprintf('inloggen/code/%s', $code));

        $response->assertRedirect('/error/pairing_code_invalid');
        $response->assertSessionMissing(AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE);
    }

    public function testLoginByCodePairingCodeExpired(): void
    {
        $pairingCodeUuid = 'foo';
        $emailAddress = 'bar';
        $pairingCode = new PairingCode(
            $pairingCodeUuid,
            'messageUuid',
            $emailAddress,
            'name',
            CarbonImmutable::yesterday(),
        );

        $this->mock(PairingCodeService::class, function (MockInterface $mock) use ($pairingCode) {
            $mock->shouldReceive('getByUuid')
                ->once()
                ->andReturn($pairingCode);
        });

        $code = urlencode(urlencode($this->generateCode('foo')));
        $response = $this->get(sprintf('inloggen/code/%s', $code));

        $response->assertRedirect('/error/pairing_code_expired');
        $response->assertSessionHas('pairingCodeResponse', [
            'error' => Error::pairingCodeExpired(),
            'emailAddress' => $emailAddress,
            'pairingCodeUuid' => $pairingCodeUuid
        ]);
        $response->assertSessionMissing(AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE);
    }

    public function testLoginByPairingCodeWhenOtherDigidSessionActiveAndUserIsAllowedToViewMessage(): void
    {
        $user = new User(User::AUTH_DIGID, $this->faker->uuid);
        $message = $this->createMessage();

        $pairingCodeUuid = $this->faker->uuid;
        $emailAddress = $this->faker->email;
        $pairingCode = new PairingCode(
            $pairingCodeUuid,
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

        $this->mock(PairingCodeService::class, function (MockInterface $mock) use ($pairingCode) {
            $mock->shouldReceive('getByUuid')
                ->once()
                ->andReturn($pairingCode);
        });

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message, $user) {
            $mock->shouldReceive('getByUuidAndPseudoBsn')
                ->once()
                ->with($message->uuid, $user->getAuthIdentifier())
                ->andReturn($message);
        });

        $code = urlencode(urlencode($this->generateCode($pairingCodeUuid)));
        $response = $this->be($user)
                        ->followingRedirects()
                        ->withSession([
                            AuthenticationService::SESSION_AUTHENTICATION_USER => $user,
                            AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $activePairingCode
                        ])
                        ->get(sprintf('inloggen/code/%s', $code));

        $response->assertLocation('');
        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_USER, $user);
        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE, $pairingCode);
    }

    public function testLoginByPairingCodeWhenOtherDigidSessionActiveAndUserIsNotAllowedToViewMessage(): void
    {
        $user = new User(User::AUTH_DIGID, $this->faker->uuid);
        $message = $this->createMessage();

        $pairingCodeUuid = $this->faker->uuid;
        $emailAddress = $this->faker->email;
        $pairingCode = new PairingCode(
            $pairingCodeUuid,
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

        $this->mock(PairingCodeService::class, function (MockInterface $mock) use ($pairingCode) {
            $mock->shouldReceive('getByUuid')
                ->once()
                ->andReturn($pairingCode);
        });

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message, $user) {
            $mock->shouldReceive('getByUuidAndPseudoBsn')
                ->once()
                ->with($message->uuid, $user->getAuthIdentifier())
                ->andThrow(new RepositoryException(Error::messageUserNotAuthorized()->value, Response::HTTP_FORBIDDEN));
        });

        $code = urlencode(urlencode($this->generateCode($pairingCodeUuid)));
        $response = $this->be($user)
                        ->withSession([
                            AuthenticationService::SESSION_AUTHENTICATION_USER => $user,
                            AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $activePairingCode
                        ])
                        ->get(sprintf('inloggen/code/%s', $code));

        $response->assertRedirect('/auth/login');
        $response->assertSessionMissing(AuthenticationService::SESSION_AUTHENTICATION_USER);
        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE, $pairingCode);
    }

    public function testLoginByPairingCodeWhenOtherOtpSessionActiveAndUserIsAllowedToViewMessage(): void
    {
        $user = new User(User::AUTH_OTP, $this->faker->uuid);
        $otpCode = new OtpCode(
            $this->faker->uuid,
            LoginType::sms(),
            $this->faker->phoneNumber,
            CarbonImmutable::tomorrow()
        );
        $message = $this->createMessage();

        $pairingCodeUuid = $this->faker->uuid;
        $emailAddress = $this->faker->email;
        $pairingCode = new PairingCode(
            $pairingCodeUuid,
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

        $this->mock(PairingCodeService::class, function (MockInterface $mock) use ($pairingCode) {
            $mock->shouldReceive('getByUuid')
                ->once()
                ->andReturn($pairingCode);
        });

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message, $otpCode) {
            $mock->shouldReceive('getByUuidAndOtpCodeUuid')
                ->once()
                ->with($message->uuid, $otpCode->uuid)
                ->andReturn($message);
        });

        $code = urlencode(urlencode($this->generateCode($pairingCodeUuid)));
        $response = $this->be($user)
                        ->followingRedirects()
                        ->withSession([
                            AuthenticationService::SESSION_AUTHENTICATION_USER => $user,
                            AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $activePairingCode,
                            OtpCodeService::SESSION_KEY => $otpCode])
                        ->get(sprintf('inloggen/code/%s', $code));

        $response->assertLocation('');
        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_USER, $user);
        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE, $pairingCode);
    }

    public function testLoginByPairingCodeWhenDigidSessionAlreadyActiveAndOtpIsAllowedToViewMessage(): void
    {
        $user = new User(User::AUTH_DIGID, $this->faker->uuid);
        $message = $this->createMessage();

        $pairingCodeUuid = $this->faker->uuid;
        $emailAddress = $this->faker->email;
        $pairingCode = new PairingCode(
            $pairingCodeUuid,
            $message->uuid,
            $emailAddress,
            $this->faker->name,
            CarbonImmutable::tomorrow(),
        );

        $activePairingCode = new PairingCode(
            $this->faker->uuid,
            $message->uuid,
            $this->faker->email,
            $this->faker->name,
            CarbonImmutable::now()->sub('2 minutes'),
        );

        $this->mock(PairingCodeService::class, function (MockInterface $mock) use ($pairingCode) {
            $mock->shouldReceive('getByUuid')
                ->once()
                ->andReturn($pairingCode);
        });

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message, $user) {
            $mock->shouldReceive('getByUuidAndPseudoBsn')
                ->once()
                ->with($message->uuid, $user->getAuthIdentifier())
                ->andReturn($message);
        });

        $code = urlencode(urlencode($this->generateCode($pairingCodeUuid)));
        $response = $this->be($user)
            ->followingRedirects()
            ->withSession([
                AuthenticationService::SESSION_AUTHENTICATION_USER => $user,
                AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $activePairingCode
            ])
            ->get(sprintf('inloggen/code/%s', $code));

        $response->assertLocation('');
        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_USER, $user);
        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE, $pairingCode);
    }

    public function testLoginByPairingCodeWhenOtpSessionAlreadyActiveAndUserIsAllowedToViewMessage(): void
    {
        $user = new User(User::AUTH_OTP, $this->faker->uuid);
        $otpCode = new OtpCode(
            $this->faker->uuid,
            LoginType::sms(),
            $this->faker->phoneNumber,
            CarbonImmutable::tomorrow()
        );
        $message = $this->createMessage();

        $pairingCodeUuid = $this->faker->uuid;
        $emailAddress = $this->faker->email;
        $pairingCode = new PairingCode(
            $pairingCodeUuid,
            $message->uuid,
            $emailAddress,
            $this->faker->name,
            CarbonImmutable::tomorrow(),
        );

        $this->mock(PairingCodeService::class, function (MockInterface $mock) use ($pairingCode) {
            $mock->shouldReceive('getByUuid')
                ->once()
                ->andReturn($pairingCode);
        });

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message, $otpCode) {
            $mock->shouldReceive('getByUuidAndOtpCodeUuid')
                ->once()
                ->with($message->uuid, $otpCode->uuid)
                ->andReturn($message);
        });

        $code = urlencode(urlencode($this->generateCode($pairingCodeUuid)));
        $response = $this->be($user)
                        ->withSession([
                            AuthenticationService::SESSION_AUTHENTICATION_USER => $user,
                            OtpCodeService::SESSION_KEY => $otpCode
                        ])
                        ->get(sprintf('inloggen/code/%s', $code));

        $response->assertRedirect('/auth/login');
        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_USER, $user);
        $response->assertSessionHas(AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE, $pairingCode);
    }

    public function testLoginByPairingCodeWhenMessageNotFound(): void
    {
        $user = new User(User::AUTH_DIGID, $this->faker->uuid);
        $message = $this->createMessage();

        $pairingCodeUuid = $this->faker->uuid;
        $emailAddress = $this->faker->email;
        $pairingCode = new PairingCode($pairingCodeUuid, $message->uuid, $emailAddress, $this->faker->name, CarbonImmutable::tomorrow());

        $this->mock(PairingCodeService::class, function (MockInterface $mock) use ($pairingCode) {
            $mock->shouldReceive('getByUuid')->once()->andReturn($pairingCode);
        });

        $this->mock(MessageService::class, function (MockInterface $mock) use ($message) {
            $mock->shouldReceive('getByUuidAndSession')
                ->once()
                ->with($message->uuid)
                ->andThrows(new RepositoryException("Message not found", Response::HTTP_NOT_FOUND));
        });

        $code = urlencode(urlencode($this->generateCode($pairingCodeUuid)));
        $response = $this->be($user)->get(sprintf('inloggen/code/%s', $code));

        $response->assertRedirect(sprintf('/error/%s', Error::messageNotFound()->value));
    }

    private function generateCode(string $data): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
        $encryptionKey = sodium_crypto_box_keypair_from_secretkey_and_publickey(
            $this->messagingAppPrivateKey,
            $this->messageBoxPublicKey
        );
        $encrypted = sodium_crypto_box(json_encode($data), $nonce, $encryptionKey);

        return base64_encode($nonce . $encrypted);
    }
}
