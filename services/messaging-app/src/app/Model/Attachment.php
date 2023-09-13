<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Model;

class Attachment
{
    public function __construct(
        public readonly string $uuid,
        public readonly ?string $messageUuid,
        public readonly string $filename,
        public readonly string $mimeType,
    ) {
    }
}
