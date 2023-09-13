<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\TestHelper;

use Carbon\CarbonImmutable;
use DateTimeInterface;

use function array_key_exists;

abstract class ModelFactory
{
    public static function getAttribute(array $attributes, string $property, $default = null)
    {
        if (array_key_exists($property, $attributes)) {
            return self::convert($attributes[$property]);
        }

        return self::convert($default);
    }

    private static function convert($value)
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        return $value;
    }
}
