<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Model;

use Carbon\CarbonInterface;

class OtpCode
{
    public function __construct(
        public readonly string $uuid,
        public readonly ?string $messageUuid,
        public readonly string $type,
        public readonly string $code,
        public readonly CarbonInterface $validUntil,
    ) {
    }
}
