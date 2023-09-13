<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\TestHelper;

use MinVWS\MessagingApp\Model\Attachment;

use function sprintf;

class AttachmentFactory extends ModelFactory
{
    public static function create(array $attributes = []): Attachment
    {
        $faker = Faker::create();

        return new Attachment(
            self::getAttribute($attributes, 'uuid', $faker->uuid),
            self::getAttribute($attributes, 'messageUuid'),
            self::getAttribute($attributes, 'filename', sprintf('%s.%s', $faker->word, $faker->fileExtension())),
            self::getAttribute($attributes, 'mimeType', $faker->mimeType()),
        );
    }
}
