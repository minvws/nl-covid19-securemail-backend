<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\Repository\Database;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use MinVWS\MessagingApi\Tests\Repository\RepositoryTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DatabaseRepositoryTestCase extends RepositoryTestCase
{
    protected function getConnection(Builder $builder): ConnectionInterface
    {
        /** @var ConnectionInterface|MockObject $connection */
        $connection = $this->mock(ConnectionInterface::class);
        $connection->method('table')
            ->willReturn($builder);
        return $connection;
    }
}
