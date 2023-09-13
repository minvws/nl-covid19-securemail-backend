<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

class DigidUser implements MessageBoxUser
{
    public function __construct(
        public readonly ?string $firstName,
        public readonly ?string $prefix,
        public readonly ?string $lastName,
        public readonly string $gender,
        public readonly string $pseudoBsn,
        public readonly string $encryptedFields,
        public readonly CarbonImmutable $lastActive,
    ) {
    }

    public function getAuthIdentifierName(): string
    {
        return User::AUTH_DIGID;
    }

    public function getAuthIdentifier(): string
    {
        return $this->pseudoBsn;
    }
}
