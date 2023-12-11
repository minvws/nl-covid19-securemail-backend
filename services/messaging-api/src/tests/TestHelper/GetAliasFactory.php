<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\TestHelper;

use Carbon\CarbonImmutable;
use MinVWS\MessagingApi\Enum\AliasStatus;
use MinVWS\MessagingApi\Model\GetAlias;

use function array_merge;

class GetAliasFactory
{
    public static function generateModel(string $uuid = null): GetAlias
    {
        $faker = Faker::create();

        /** @var AliasStatus $status */
        $status = $faker->randomElement([AliasStatus::NEW(), AliasStatus::VERIFIED()]);

        return new GetAlias(
            $uuid ?? $faker->uuid,
            $status,
            CarbonImmutable::instance($faker->dateTime),
            $faker->uuid
        );
    }

    public static function generateDatabaseResult(array $getAlias = []): object
    {
        $faker = Faker::create();

        $generated = [
            'uuid' => $faker->uuid,
            'mailbox_digid_identifier' => $faker->uuid,
            'updated_at' => $faker->dateTime()->format('Y-m-d H:i:s'),
        ];

        return (object) array_merge($generated, $getAlias);
    }
}
