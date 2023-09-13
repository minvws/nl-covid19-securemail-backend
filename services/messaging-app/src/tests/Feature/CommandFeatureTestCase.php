<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature;

use Illuminate\Database\ConnectionInterface;
use MinVWS\MessagingApp\Tests\CommandTestCase;
use MinVWS\MessagingApp\Tests\Feature\Service\AssertionTrait;
use MinVWS\MessagingApp\Tests\Feature\Service\CreateModelTrait;

abstract class CommandFeatureTestCase extends CommandTestCase
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
