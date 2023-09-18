<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;

class User implements Authenticatable
{
    public const AUTH_DIGID = 'digid';
    public const AUTH_OTP = 'otp';

    public function __construct(
        private readonly string $identifierName,
        private readonly string $identifier,
    ) {
    }

    public function getAuthIdentifierName(): string
    {
        return $this->identifierName;
    }

    public function getAuthIdentifier(): string
    {
        return $this->identifier;
    }

    public function getAuthPassword(): void
    {
    }

    public function getRememberToken(): void
    {
    }

    public function setRememberToken($value): void
    {
    }

    public function getRememberTokenName(): void
    {
    }
}
