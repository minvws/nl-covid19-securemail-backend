<?php

declare(strict_types=1);

namespace App\Models;

class MessageAuthenticationProperties
{
    public function __construct(
        public readonly string $uuid,
        public readonly bool $identityRequired,
        public readonly bool $hasIdentity,
        public readonly ?string $phoneNumber,
    ) {
    }

    public function hasPhoneNumber(): bool
    {
        return $this->phoneNumber !== null;
    }
}
