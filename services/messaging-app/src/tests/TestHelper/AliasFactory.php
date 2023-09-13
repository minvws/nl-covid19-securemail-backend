<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\TestHelper;

use MinVWS\MessagingApp\Model\Alias;

class AliasFactory extends ModelFactory
{
    public static function create(array $attributes = []): Alias
    {
        $faker = Faker::create();

        return new Alias(
            self::getAttribute($attributes, 'uuid', $faker->uuid),
            self::getAttribute($attributes, 'mailboxUuid'),
            self::getAttribute($attributes, 'platform', $faker->domainWord),
            self::getAttribute($attributes, 'platformIdentifier', $faker->uuid),
            self::getAttribute($attributes, 'expiresAt', $faker->optional()->dateTime),
            self::getAttribute($attributes, 'emailAddress', $faker->safeEmail),
            self::getAttribute($attributes, 'createdAt', $faker->dateTimeBetween('-30 days')),
        );
    }
}
