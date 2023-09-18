<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\TestHelper;

use Carbon\CarbonImmutable;
use DateTime;
use MinVWS\MessagingApi\Model\GetMessage;

use function array_merge;

class GetMessageFactory
{
    public static function generateModel(string $uuid = null): GetMessage
    {
        $faker = Faker::create();

        /** @var DateTime|null $notificationSentAt */
        $notificationSentAt = $faker->optional()->dateTime;

        /** @var DateTime|null $receivedAt */
        $receivedAt = $faker->optional()->dateTime;

        /** @var DateTime|null $bouncedAt */
        $bouncedAt = $faker->optional()->dateTime;

        /** @var DateTime|null $otpAuthFailedAt */
        $otpAuthFailedAt = $faker->optional()->dateTime;

        /** @var DateTime|null $otpIncorrectPhoneAt */
        $otpIncorrectPhoneAt = $faker->optional()->dateTime;

        /** @var DateTime|null $digidAuthFailedAt */
        $digidAuthFailedAt = $faker->optional()->dateTime;

        /** @var DateTime|null $firstReadAt */
        $firstReadAt = $faker->optional()->dateTime;

        /** @var DateTime|null $revokedAt */
        $revokedAt = $faker->optional()->dateTime;

        /** @var DateTime|null $expiredAt */
        $expiredAt = $faker->optional()->dateTime;

        return new GetMessage(
            $uuid ?? $faker->uuid,
            $notificationSentAt ? CarbonImmutable::instance($notificationSentAt) : null,
            $receivedAt ? CarbonImmutable::instance($receivedAt) : null,
            $bouncedAt ? CarbonImmutable::instance($bouncedAt) : null,
            $otpAuthFailedAt ? CarbonImmutable::instance($otpAuthFailedAt) : null,
            $otpIncorrectPhoneAt ? CarbonImmutable::instance($otpIncorrectPhoneAt) : null,
            $digidAuthFailedAt ? CarbonImmutable::instance($digidAuthFailedAt) : null,
            $firstReadAt ? CarbonImmutable::instance($firstReadAt) : null,
            $revokedAt ? CarbonImmutable::instance($revokedAt) : null,
            $expiredAt ? CarbonImmutable::instance($expiredAt) : null,
        );
    }

    public static function generateDatabaseResult(array $getMessage = []): object
    {
        $faker = Faker::create();

        $generated = [
            'uuid' => $faker->uuid,
            'mailbox_uuid' => $faker->uuid,
            'notification_sent_at' => $faker->boolean ? $faker->dateTime()->format('Y-m-d H:i:s') : null,
            'is_read' => $faker->boolean,
            'pairing_code_paired_at' => $faker->boolean ? $faker->dateTime()->format('Y-m-d H:i:s') : null,
            'updated_at' => $faker->boolean ? $faker->dateTime()->format('Y-m-d H:i:s') : null,
            'received_at' => $faker->boolean ? $faker->dateTime->format('Y-m-d H:i:s') : null,
            'bounced_at' => $faker->boolean ? $faker->dateTime->format('Y-m-d H:i:s') : null,
            'otp_auth_failed_at' => $faker->boolean ? $faker->dateTime->format('Y-m-d H:i:s') : null,
            'otp_incorrect_phone_at' => $faker->boolean ? $faker->dateTime->format('Y-m-d H:i:s') : null,
            'digid_auth_failed_at' => $faker->boolean ? $faker->dateTime->format('Y-m-d H:i:s') : null,
            'first_read_at' => $faker->boolean ? $faker->dateTime->format('Y-m-d H:i:s') : null,
            'revoked_at' => $faker->boolean ? $faker->dateTime->format('Y-m-d H:i:s') : null,
            'expired_at' => $faker->boolean ? $faker->dateTime->format('Y-m-d H:i:s') : null,
        ];

        return (object) array_merge($generated, $getMessage);
    }
}
