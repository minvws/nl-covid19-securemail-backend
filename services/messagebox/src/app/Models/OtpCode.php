<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Enums\LoginType;
use Carbon\CarbonImmutable;

class OtpCode
{
    public function __construct(
        public readonly string $uuid,
        public readonly LoginType $loginType,
        public readonly string $phoneNumber,
        public readonly CarbonImmutable $validUntil,
    ) {
    }
}
