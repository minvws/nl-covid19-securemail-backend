<?php

namespace App\Providers;

use App\Helpers\MaxDigidTokenValidator;
use App\Helpers\MaxDigidTokenValidatorInterface;
use App\Providers\Auth\MessageBoxUserProvider;
use App\Services\AuthenticationService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();

        Auth::provider('messagebox', function ($app): MessageBoxUserProvider {
            return new MessageBoxUserProvider(
                $app->get(AuthenticationService::class),
            );
        });

        $this->app->bind(MaxDigidTokenValidatorInterface::class, MaxDigidTokenValidator::class);
    }
}
