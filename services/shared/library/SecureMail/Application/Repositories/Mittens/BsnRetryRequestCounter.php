<?php

declare(strict_types=1);

namespace SecureMail\Shared\Application\Repositories\Mittens;

class BsnRetryRequestCounter
{
    private static int $retryRequestCount = 0;

    public static function get(): int
    {
        return self::$retryRequestCount;
    }

    public static function increment(): int
    {
        return self::$retryRequestCount++;
    }

    public static function reset(): void
    {
        self::$retryRequestCount = 0;
    }
}
