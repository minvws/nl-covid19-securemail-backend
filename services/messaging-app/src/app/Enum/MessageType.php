<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Enum;

use MyCLabs\Enum\Enum;

class MessageType extends Enum
{
    private const DIRECT = 'direct';
    private const SECURE = 'secure';

    public static function DIRECT(): self
    {
        return new self(self::DIRECT);
    }

    public static function SECURE(): self
    {
        return new self(self::SECURE);
    }
}
