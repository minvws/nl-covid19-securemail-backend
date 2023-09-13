<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use JsonSerializable;

class MessagePreview implements JsonSerializable
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $fromName,
        public readonly string $subject,
        public readonly CarbonInterface $createdAt,
        public readonly bool $isRead,
        public readonly bool $hasAttachments,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'fromName' => $this->fromName,
            'subject' => $this->subject,
            'createdAt' => $this->createdAt->format('c'),
            'isRead' => $this->isRead,
            'hasAttachments' => $this->hasAttachments,
        ];
    }
}
