<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Enum;

use MyCLabs\Enum\Enum;

class AliasStatus extends Enum
{
    private const NEW = 'new';
    private const VERIFIED = 'verified';

    public static function NEW(): self
    {
        return new self(self::NEW);
    }

    public static function VERIFIED(): self
    {
        return new self(self::VERIFIED);
    }
}
