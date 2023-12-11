<?php

declare(strict_types=1);

namespace App\Models;

class Attachment
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $name,
        public readonly string $mimeType,
    ) {
    }
}
