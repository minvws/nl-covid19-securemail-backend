<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\Repository\Database;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use MinVWS\MessagingApi\Model\GetAlias;
use MinVWS\MessagingApi\Repository\Database\DatabaseAliasRepository;
use MinVWS\MessagingApi\Tests\TestHelper\GetAliasFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class DatabaseAliasRepostitoryTest extends DatabaseRepositoryTestCase
{
    /**
     * @dataProvider getStatusUpdatesDataProvider
     */
    public function testGetStatusUpdates(?string $digidIdentifier, string $expectedStatus): void
    {
        $queryResult = new Collection([
            GetAliasFactory::generateDatabaseResult([
                'uuid' => 'foo',
                'mailbox_digid_identifier' => $digidIdentifier,
            ]),
        ]);
        $aliasses = $this->getStatusUpdates(CarbonImmutable::now(), 1, $queryResult);

        $this->assertEquals('foo', $aliasses[0]->uuid);
        $this->assertEquals($expectedStatus, $aliasses[0]->status->getValue());
    }

    public function getStatusUpdatesDataProvider(): array
    {
        return [
            'new' => [null, 'new'],
            'verified' => ['foo', 'verified'],
        ];
    }

    /**
     * @return GetAlias[]
     */
    private function getStatusUpdates(CarbonInterface $since, ?int $limit, object $queryResult): array
    {
        /** @var Builder|MockObject $builder */
        $builder = $this->mock(Builder::class);
        $builder->expects($this->exactly(2))
            ->method('addSelect')
            ->withConsecutive(
                ['alias.*'],
                ['mailbox.digid_identifier AS mailbox_digid_identifier'],
            )
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('leftJoin')
            ->with('mailbox AS mailbox')
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('where')
            ->with('alias.updated_at', '>', $since->format('c'))
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('orderBy')
            ->with('alias.updated_at')
            ->willReturn($builder);
        if ($limit !== null) {
            $builder->expects($this->once())
                ->method('limit')
                ->with($limit)
                ->willReturn($builder);
        }
        $builder->expects($this->once())
            ->method('get')
            ->willReturn($queryResult);

        $repository = $this->getRepository($builder);

        return $repository->getStatusUpdates($since, $limit);
    }

    private function getRepository(Builder $builder): DatabaseAliasRepository
    {
        $connection = $this->getConnection($builder);
        $logger = new NullLogger();

        return new DatabaseAliasRepository($connection, $logger);
    }
}
