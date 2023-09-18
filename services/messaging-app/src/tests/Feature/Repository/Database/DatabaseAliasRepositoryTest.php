<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Repository\Database;

use MinVWS\MessagingApp\Repository\Database\DatabaseAliasRepository;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;

class DatabaseAliasRepositoryTest extends FeatureTestCase
{
    protected DatabaseAliasRepository $aliasRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aliasRepository = $this->getContainer()->get(DatabaseAliasRepository::class);
    }

    public function testDeleteExpired(): void
    {
        $alias = $this->createAlias(['expiresAt' => $this->faker->dateTime('-1 day')]);

        $this->aliasRepository->deleteExpired();

        $this->assertDatabaseCount('alias', ['uuid' => $alias->uuid], 0);
    }

    public function testDeleteNonExpiredWithoutMailbox(): void
    {
        $alias = $this->createAlias([
            'mailboxUuid' => null,
            'expiresAt' => $this->faker->dateTimeBetween('+ 1 day', '+3 days'),
        ]);

        $this->aliasRepository->deleteExpired();

        $this->assertDatabaseCount('alias', ['uuid' => $alias->uuid], 0);
    }

    public function testNotDeleteNonExpired(): void
    {
        $alias = $this->createAlias(['expiresAt' => $this->faker->dateTimeBetween('+ 1 day', '+3 days')]);

        $this->aliasRepository->deleteExpired();

        $this->assertDatabaseCount('alias', ['uuid' => $alias->uuid], 1);
    }
}
