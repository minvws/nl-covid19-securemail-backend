<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Repository\Database;

use MinVWS\MessagingApp\Repository\Database\DatabasePairingCodeRepository;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;

class DatabasePairingCodeRepositoryTest extends FeatureTestCase
{
    protected DatabasePairingCodeRepository $pairingCodeRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pairingCodeRepository = $this->getContainer()->get(DatabasePairingCodeRepository::class);
    }

    public function testDeleteExpired(): void
    {
        $pairingCode = $this->createPairingCode(['validUntil' => $this->faker->dateTime('-1 day')]);

        $this->pairingCodeRepository->deleteExpired();

        $this->assertDatabaseCount('pairing_code', ['uuid' => $pairingCode->uuid], 0);
    }

    public function testDeleteExpiredWhenNoAttachedAlias(): void
    {
        $pairingCode = $this->createPairingCode([
            'aliasUuid' => null,
            'validUntil' => $this->faker->dateTimeBetween('+1 day', '+3 days'),
        ]);

        $this->pairingCodeRepository->deleteExpired();

        $this->assertDatabaseCount('pairing_code', ['uuid' => $pairingCode->uuid], 0);
    }

    public function testDeleteExpiredWhenNoAttachedMessage(): void
    {
        $pairingCode = $this->createPairingCode([
            'messageUuid' => null,
            'validUntil' => $this->faker->dateTimeBetween('+1 day', '+3 days'),
        ]);

        $this->pairingCodeRepository->deleteExpired();

        $this->assertDatabaseCount('pairing_code', ['uuid' => $pairingCode->uuid], 0);
    }

    public function testNotDeleteNotExpired(): void
    {
        $pairingCode = $this->createPairingCode(['validUntil' => $this->faker->dateTimeBetween('+1 day', '+3 days')]);

        $this->pairingCodeRepository->deleteExpired();

        $this->assertDatabaseCount('pairing_code', ['uuid' => $pairingCode->uuid], 1);
    }
}
