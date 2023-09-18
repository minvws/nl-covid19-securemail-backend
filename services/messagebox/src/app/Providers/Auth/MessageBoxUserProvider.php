<?php

declare(strict_types=1);

namespace App\Providers\Auth;

use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

use function is_string;

class MessageBoxUserProvider implements UserProvider
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
    ) {
    }

    /**
     * @throws AuthenticationException
     */
    public function retrieveById($identifier): ?User
    {
        if (is_string($identifier)) {
            return $this->authenticationService->retrieveUserFromSession($identifier);
        }

        return null;
    }

    public function retrieveByToken($identifier, $token): ?User
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): ?User
    {
        return null;
    }

    public function retrieveByCredentials(array $credentials): ?User
    {
        return null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return false;
    }
}
