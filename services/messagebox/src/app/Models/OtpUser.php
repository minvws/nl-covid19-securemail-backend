<?php

declare(strict_types=1);

namespace App\Models;

class OtpUser implements MessageBoxUser
{
    public function __construct(
        private readonly string $aliasId,
    ) {
    }

    public function getAuthIdentifierName(): string
    {
        return User::AUTH_OTP;
    }

    public function getAuthIdentifier(): string
    {
        return $this->aliasId;
    }
}
