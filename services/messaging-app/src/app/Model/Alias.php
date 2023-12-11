<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Model;

use Carbon\CarbonInterface;

class Alias
{
    public function __construct(
        public readonly string $uuid,
        public ?string $mailboxUuid,
        public readonly string $platform,
        public readonly string $platformIdentifier,
        public ?CarbonInterface $expiresAt,
        public readonly string $emailAddress,
        public readonly CarbonInterface $createdAt,
    ) {
    }
}
