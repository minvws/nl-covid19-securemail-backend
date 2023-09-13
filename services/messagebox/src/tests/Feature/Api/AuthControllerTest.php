<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\MessageAuthenticationProperties;
use App\Models\PairingCode;
use App\Models\User;
use App\Repositories\MessageRepository;
use App\Services\AuthenticationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Mockery\MockInterface;
use Tests\Feature\ControllerTestCase;

use function json_encode;

class AuthControllerTest extends ControllerTestCase
{
    public function testOptions(): void
    {
        $user = new User(User::AUTH_OTP, $this->faker->uuid());
        $response = $this->actingAs($user)->get('api/v1/auth/options');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOptionsWithMessage(): void
    {
        $this->mock(MessageRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAuthenticationProperties')
                ->once()
                ->andReturn(new MessageAuthenticationProperties(
                    'bar',
                    false,
                    true,
                    'phone',
                ));
        });

        $user = new User(User::AUTH_OTP, $this->faker->uuid());
        $pairingCode = new PairingCode('foo', 'bar', 'baz', 'qux', CarbonImmutable::now());
        $response = $this->actingAs($user)
            ->withSession([AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $pairingCode])
            ->get('api/v1/auth/options');

        $expectedOutput = json_encode([
            'loginTypes' => ['digid', 'sms'],
            'name' => 'qux',
        ]);
        $this->assertEquals($expectedOutput, $response->getContent());
    }

    public function testOptionsWithMessageAndIdentityRequired(): void
    {
        $this->mock(MessageRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('getAuthenticationProperties')
                ->once()
                ->andReturn(new MessageAuthenticationProperties(
                    'bar',
                    true,
                    true,
                    'phone',
                ));
        });

        $user = new User(User::AUTH_OTP, $this->faker->uuid());
        $pairingCode = new PairingCode('foo', 'bar', 'baz', 'qux', CarbonImmutable::now());
        $response = $this->actingAs($user)
            ->withSession([AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $pairingCode])
            ->get('api/v1/auth/options');

        $expectedOutput = json_encode([
            'loginTypes' => ['digid'],
            'name' => 'qux',
        ]);
        $this->assertEquals($expectedOutput, $response->getContent());
    }

    /**
     * @dataProvider sessionLifetimeDataProvider
     */
    public function testKeepAlive(int $lifetime): void
    {
        $this->config->set('auth.authentication_session_lifetime_in_seconds', $lifetime * 60);

        $now = CarbonImmutable::createFromDate(2000, 1, 1);
        CarbonImmutable::setTestNow($now);

        $response = $this->get('/api/v1/auth/keep-alive');
        $response->assertHeader('X-Session-Expiry-Date', $now->addMinutes($lifetime)->format('c'));

        CarbonImmutable::setTestNow(); // reset
    }

    public function sessionLifetimeDataProvider(): array
    {
        return [
            '15 minutes' => [15],
            '60 minutes' => [60],
        ];
    }

    public function testLogout(): void
    {
        $user = new User(User::AUTH_OTP, $this->faker->uuid());
        $pairingCode = new PairingCode('foo', 'bar', 'baz', 'qux', CarbonImmutable::now());
        $this->actingAs($user)->withSession([AuthenticationService::SESSION_AUTHENTICATION_PAIRING_CODE => $pairingCode]);

        $this->assertEquals(Auth::check(), true);

        $response = $this->get('api/v1/auth/logout');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(Auth::check(), false);
    }
}
