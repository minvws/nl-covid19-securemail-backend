<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\TestHelper;

use MinVWS\MessagingApp\Model\PairingCode;

class PairingCodeFactory extends ModelFactory
{
    public static function generateModel(array $attributes = []): PairingCode
    {
        $faker = Faker::create();

        return new PairingCode(
            self::getAttribute($attributes, 'uuid', $faker->uuid),
            self::getAttribute($attributes, 'aliasUuid'),
            self::getAttribute($attributes, 'messageUuid'),
            self::getAttribute($attributes, 'code', $faker->uuid),
            self::getAttribute($attributes, 'validUntil', $faker->dateTimeBetween('-1 day', '1 day')),
            self::getAttribute($attributes, 'pairedAt', $faker->optional()->dateTimeBetween('-1 week')),
            self::getAttribute($attributes, 'previousCode', $faker->optional()->uuid),
        );
    }
}
