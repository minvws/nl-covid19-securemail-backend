<?php

declare(strict_types=1);

namespace SecureMail\Shared\Application\Models;

class MittensUserInfo
{
    public function __construct(
        public readonly string $firstName,
        public readonly ?string $prefix,
        public readonly string $lastName,
        public readonly string $gender,
        public readonly string $guid,
        public readonly string $encrypted,
    ) {
    }
}
