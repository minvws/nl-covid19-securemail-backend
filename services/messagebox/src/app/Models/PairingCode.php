<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use JsonSerializable;

class PairingCode implements JsonSerializable
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $messageUuid,
        public readonly string $emailAddress,
        public readonly string $toName,
        public readonly CarbonImmutable $validUntil,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'messageUuid' => $this->messageUuid,
            'emailAddress' => $this->emailAddress,
            'toName' => $this->toName,
            'validUntil' => $this->validUntil,
        ];
    }
}
