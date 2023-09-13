<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Model;

class SaveAttachment
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $filename,
        public readonly string $content,
        public readonly string $mimeType,
    ) {
    }
}
