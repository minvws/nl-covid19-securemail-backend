<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Model;

use Carbon\CarbonInterface;

class PairingCode
{
    public function __construct(
        public readonly string $uuid,
        public readonly ?string $aliasUuid,
        public readonly ?string $messageUuid,
        public string $code,
        public CarbonInterface $validUntil,
        public readonly ?CarbonInterface $pairedAt = null,
        public ?string $previousCode = null,
    ) {
    }
}
