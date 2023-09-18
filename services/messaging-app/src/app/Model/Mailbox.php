<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Model;

class Mailbox
{
    public function __construct(
        public readonly string $uuid,
        public readonly ?string $pseudoBsn,
    ) {
    }
}
