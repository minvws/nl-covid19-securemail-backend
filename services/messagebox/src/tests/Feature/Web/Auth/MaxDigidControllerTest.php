<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Auth;

use App\Helpers\MaxDigidTokenValidatorInterface;
use App\Models\Enums\Error;
use App\Models\PairingCode;
use App\Providers\Auth\MaxDigidClient;
use App\Repositories\MessageRepository;
use App\Services\AuthenticationService;
use App\Services\MaxConfigurationService;
use Carbon\CarbonImmutable;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use SecureMail\Shared\Application\Repositories\PseudoBsnRepository;
use Tests\Feature\ControllerTestCase;

use function json_encode;

/**
 * @group max-digid
 */
class MaxDigidControllerTest extends ControllerTestCase
{
    public function testMaxDigidLoginRedirectSuccess(): void
    {
        $authorizationEndpoint = 'https://max.digid.endpoint.nl/authorize';
        $this->mock(MaxConfigurationService::class, function (MockInterface $mock) use ($authorizationEndpoint): void {
            $mock->shouldReceive('getOidcConfiguration')
                ->once()
                ->andReturn(['authorization_endpoint' => $authorizationEndpoint]);
        });

        $response = $this->get('/auth/digid');
        $response->assertRedirectContains($authorizationEndpoint);
    }

    public function testMaxDigidLoginWithCallbackSuccess(): void
    {
        $this->app->bind(MaxDigidTokenValidatorInterface::class, MaxDigidTokenValidatorMock::class);

        $this->mock(MaxDigidClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendRequest')
                ->once()
                ->andReturn(new Response(200, [], json_encode(['id_token' => $this->faker->uuid])));
        });

        $response = $this->withSession($this->getOpenIdSessionVars())
            ->get('/auth/callback?code=4a5153487f5c4969bceedcbcc01bc1dd&state=V2hhdCBzdGF0ZSBkbyB3ZSBuZWVkPw');
        $response->assertRedirect('/');

        $this->assertTrue(Session::has(AuthenticationService::SESSION_AUTHENTICATION_USER));
    }

    public function testMaxDigidLoginForUnallowedMessage(): void
    {
        $this->app->bind(MaxDigidTokenValidatorInterface::class, MaxDigidTokenValidatorMock::class);

        $messageUuid = $this->faker->uuid;
        $toName = $this->faker->name;

        $this->mock(MaxDigidClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendRequest')
                ->once()
                ->andReturn(new Response(200, [], json_encode(['id_token' => $this->faker->uuid])));
        });

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($messageUuid): void {
            $mock->shouldReceive('getByUuidAndPseudoBsn')
                ->once()
                ->with($messageUuid, '0fb60aa4-1ef6-4f05-aec3-5b84b62cbcf4')
                ->andThrows(RepositoryException::class, Error::messageUserNotAuthorized(), 403);
        });

        $sessionVars = $this->getOpenIdSessionVars();
        $sessionVars[AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE] = new PairingCode(
            $this->faker->uuid,
            $messageUuid,
            $this->faker->safeEmail,
            $toName,
            CarbonImmutable::now()->addHour()
        );

        $response = $this->withSession($sessionVars)
            ->get('/auth/callback?code=4a5153487f5c4969bceedcbcc01bc1dd&state=V2hhdCBzdGF0ZSBkbyB3ZSBuZWVkPw');
        $response->assertRedirect('/error/message_user_not_authorized');

        $response->assertSessionHas([
            'digidResponse' => [
                'status' => 'error',
                'error' => Error::messageUserNotAuthorized(),
                'name' => $toName
            ]
        ]);

        $this->assertFalse(Session::has('pseudoBsn'));
    }

    public function testMaxDigidLoginWithMissingSessionVarsFailed(): void
    {
        $response = $this->get(
            '/auth/callback?code=4a5153487f5c4969bceedcbcc01bc1dd&state=V2hhdCBzdGF0ZSBkbyB3ZSBuZWVkPw'
        );
        $response->assertSessionHas('digidResponse', [
            'status' => 'error',
            'error' => Error::digidAuthError(),
        ]);
        $response->assertRedirect('/');
    }

    public function testMaxDigidLoginFailed(): void
    {
        $this->mock(MaxDigidClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendRequest')
                ->once()
                ->andReturn(new Response(400, []));
        });

        $response = $this->withSession($this->getOpenIdSessionVars())
            ->get('/auth/callback?code=4a5153487f5c4969bceedcbcc01bc1dd&state=V2hhdCBzdGF0ZSBkbyB3ZSBuZWVkPw');
        $response->assertSessionHas('digidResponse', [
            'status' => 'error',
            'error' => Error::digidAuthError(),
        ]);
        $response->assertRedirect('/');
    }

    public function testMaxDigidLoginCancelled(): void
    {
        $this->mock(MaxDigidClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendRequest')
                ->once()
                ->andReturn(new Response(400, [], json_encode(['error' => 'saml_authn_failed'])));
        });

        $response = $this->withSession($this->getOpenIdSessionVars())
            ->get('/auth/callback?code=4a5153487f5c4969bceedcbcc01bc1dd&state=V2hhdCBzdGF0ZSBkbyB3ZSBuZWVkPw');
        $response->assertSessionHas('digidResponse', [
            'status' => 'error',
            'error' => Error::digidCanceled(),
        ]);
        $response->assertRedirect('/');
    }

    public function testMaxDigidLoginSuccessButNoBsnInResponse(): void
    {
        $this->config->set('services.pseudo_bsn_service', 'mittens');

        $this->app->bind(MaxDigidTokenValidatorInterface::class, MaxDigidTokenValidatorMock::class);

        $this->mock(MaxDigidClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendRequest')
                ->once()
                ->andReturn(new Response(200, [], json_encode(['id_token' => $this->faker->uuid])));
        });

        $this->mock(PseudoBsnRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getByDigidToken')
                ->andThrow(RepositoryException::class, 'no data-field in response');
        });

        $response = $this->withSession($this->getOpenIdSessionVars())
            ->get('/auth/callback?code=4a5153487f5c4969bceedcbcc01bc1dd&state=V2hhdCBzdGF0ZSBkbyB3ZSBuZWVkPw');
        $response->assertRedirect('/');

        $response->assertSessionHas('digidResponse', [
            'status' => 'error',
            'error' => Error::digidAuthError(),
        ]);
    }

    public function testDigidLoginSuccessButBsnRequestFailed(): void
    {
        $this->config->set('services.pseudo_bsn_service', 'mittens');

        $this->app->bind(MaxDigidTokenValidatorInterface::class, MaxDigidTokenValidatorMock::class);

        $this->mock(MaxDigidClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendRequest')
                ->once()
                ->andReturn(new Response(200, [], json_encode(['id_token' => $this->faker->uuid()])));
        });

        $this->mock(PseudoBsnRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getByDigidToken')
                ->andThrow(new RepositoryException('not found'));
        });

        $response = $this->withSession($this->getOpenIdSessionVars())
            ->get('/auth/callback?code=4a5153487f5c4969bceedcbcc01bc1dd&state=V2hhdCBzdGF0ZSBkbyB3ZSBuZWVkPw');
        $response->assertRedirect('/');

        $response->assertSessionHas('digidResponse', [
            'status' => 'error',
            'error' => Error::digidAuthError(),
        ]);
    }

    private function getOpenIdSessionVars(): array
    {
        return [
            'openidstate' => 'V2hhdCBzdGF0ZSBkbyB3ZSBuZWVkPw',
            'openidcodeverifier' => 'RzU0NHlHN0pVMmlseTdOWDZtelZNb1k5NjdTYjJlVHE'
        ];
    }
}
