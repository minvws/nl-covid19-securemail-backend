<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class Message
{
    /**
     * @param Collection<Attachment> $attachments
     */
    public function __construct(
        public readonly string $uuid,
        public readonly string $aliasUuid,
        public readonly string $fromName,
        public readonly string $toName,
        public readonly string $subject,
        public readonly string $text,
        public readonly string $footer,
        public readonly CarbonInterface $createdAt,
        public readonly ?CarbonInterface $expiresAt,
        public readonly Collection $attachments,
        public readonly ?string $attachmentsEncryptionKey,
    ) {
    }
}
