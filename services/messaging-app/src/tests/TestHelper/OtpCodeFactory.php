<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\TestHelper;

use MinVWS\MessagingApp\Model\OtpCode;

class OtpCodeFactory extends ModelFactory
{
    public static function generateModel(array $attributes = []): OtpCode
    {
        $faker = Faker::create();

        return new OtpCode(
            self::getAttribute($attributes, 'uuid', $faker->uuid),
            self::getAttribute($attributes, 'messageUuid'),
            self::getAttribute($attributes, 'type', $faker->word),
            self::getAttribute($attributes, 'code', $faker->uuid),
            self::getAttribute($attributes, 'validUntil', $faker->dateTimeBetween('-1 day', '1 day')),
        );
    }
}
