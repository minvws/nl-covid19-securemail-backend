<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature;

use Illuminate\Database\ConnectionInterface;
use MinVWS\MessagingApp\Tests\Feature\Service\AssertionTrait;
use MinVWS\MessagingApp\Tests\Feature\Service\CreateModelTrait;
use MinVWS\MessagingApp\Tests\TestCase;

class FeatureTestCase extends TestCase
{
    use CreateModelTrait;
    use AssertionTrait;

    protected ConnectionInterface $databaseConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseConnection = $this->getContainer()->get(ConnectionInterface::class);
        $this->databaseConnection->beginTransaction();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->databaseConnection->rollBack();
    }
}
