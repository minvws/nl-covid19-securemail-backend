<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\TestHelper;

use Illuminate\Encryption\Encrypter;
use MinVWS\MessagingApp\Model\Message;

class MessageFactory extends ModelFactory
{
    public static function generateModel(array $attributes = []): Message
    {
        $faker = Faker::create();

        $attachmentsEncrptionKey = $faker->optional()->passthrough(Encrypter::generateKey('aes-128-cbc'));

        return new Message(
            self::getAttribute($attributes, 'uuid', $faker->uuid),
            self::getAttribute($attributes, 'platform', $faker->domainWord),
            self::getAttribute($attributes, 'aliasUuid'),
            self::getAttribute($attributes, 'mailboxUuid'),
            self::getAttribute($attributes, 'fromName', $faker->company),
            self::getAttribute($attributes, 'fromEmail', $faker->safeEmail),
            self::getAttribute($attributes, 'toName', $faker->company),
            self::getAttribute($attributes, 'toEmail', $faker->safeEmail),
            self::getAttribute($attributes, 'phoneNumber', $faker->optional()->phoneNumber),
            self::getAttribute($attributes, 'subject', $faker->word),
            self::getAttribute($attributes, 'text', $faker->paragraph),
            self::getAttribute($attributes, 'footer', $faker->paragraph),
            self::getAttribute($attributes, 'attachmentsEncryptionKey', $attachmentsEncrptionKey),
            self::getAttribute($attributes, 'expiresAt', $faker->optional()->dateTimeBetween('-30 days', '30 days')),
            self::getAttribute($attributes, 'createdAt', $faker->dateTimeBetween('-30 days')),
            self::getAttribute($attributes, 'identityRequired', $faker->boolean),
            self::getAttribute($attributes, 'notificationSentAt', $faker->optional()->dateTimeBetween('-30 days')),
            self::getAttribute($attributes, 'firstReadAt', $faker->optional()->dateTimeBetween('-14 days')),
            self::getAttribute($attributes, 'otpIncorrectPhone', $faker->optional()->dateTimeBetween('-14 days')),
        );
    }
}
