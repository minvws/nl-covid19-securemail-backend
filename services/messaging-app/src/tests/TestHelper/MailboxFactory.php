<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\TestHelper;

use MinVWS\MessagingApp\Model\Mailbox;

class MailboxFactory extends ModelFactory
{
    public static function create(array $attributes = []): Mailbox
    {
        $faker = Faker::create();

        return new Mailbox(
            self::getAttribute($attributes, 'uuid', $faker->uuid),
            self::getAttribute($attributes, 'pseudoBsn', $faker->optional()->uuid),
        );
    }
}
