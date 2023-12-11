<?php

namespace MinVWS\MessagingApi\Tests\TestHelper;

class PostMessageBodyCreator
{
    public static function getValidMessageBody(): array
    {
        $faker = Faker::create();

        return [
            'type' => $faker->randomElement(['direct', 'secure']),
            'aliasId' => $faker->uuid,
            'fromName' => $faker->company,
            'fromEmail' => $faker->safeEmail,
            'toName' => $faker->name,
            'toEmail' => $faker->safeEmail,
            'subject' => $faker->sentence,
            'text' => $faker->paragraph,
            'footer' => $faker->paragraph,
            'identityRequired' => false,
        ];
    }
}
