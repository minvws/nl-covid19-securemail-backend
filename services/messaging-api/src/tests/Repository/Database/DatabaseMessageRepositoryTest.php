<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\Repository\Database;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use MinVWS\MessagingApi\Model\GetMessage;
use MinVWS\MessagingApi\Repository\Database\DatabaseMessageRepository;
use MinVWS\MessagingApi\Tests\TestHelper\GetMessageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class DatabaseMessageRepositoryTest extends DatabaseRepositoryTestCase
{
    /**
     * @dataProvider getStatusUpdatesDataProvider
     */
    public function testGetStatusUpdates(
        ?string $notificationSentAt,
        ?string $pairedAt,
        ?string $mailboxUuid,
        bool $isRead,
        string $expectedStatus,
    ): void {
        $queryResult = new Collection([
            GetMessageFactory::generateDatabaseResult([
                'uuid' => 'foo',
                'mailbox_uuid' => $mailboxUuid,
                'notification_sent_at' => $notificationSentAt,
                'pairing_code_paired_at' => $pairedAt,
                'is_read' => $isRead,
            ]),
        ]);
        $messages = $this->getStatusUpdates(CarbonImmutable::now(), 1, $queryResult);

        $this->assertEquals('foo', $messages[0]->uuid);
        $this->assertEquals($notificationSentAt, $messages[0]->notificationSentAt);
    }

    public function getStatusUpdatesDataProvider(): array
    {
        $dateString = CarbonImmutable::now()->format('Y-m-d H:i:s');

        return [
            'new' => [null, null, null, false, 'new'],
            'sent' => [$dateString, null, null, false, 'sent'],
            'pairingCodeOk' => [$dateString, $dateString, null, false, 'pairingCodeOk'],
            'identified' => [$dateString, $dateString, 'foo', false, 'identified'],
            'read' => [$dateString, $dateString, 'foo', true, 'read'],
        ];
    }

    /**
     * @return GetMessage[]
     *
     * @throws RepositoryException
     */
    private function getStatusUpdates(CarbonInterface $since, ?int $limit, object $queryResult): array
    {
        /** @var Builder|MockObject $builder */
        $builder = $this->mock(Builder::class);
        $builder->expects($this->exactly(2))
            ->method('addSelect')
            ->withConsecutive(
                ['message.*'],
                ['pairing_code.paired_at AS pairing_code_paired_at'],
            )
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('leftJoin')
            ->with('pairing_code AS pairing_code')
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('where')
            ->with('message.updated_at', '>', $since->format('c'))
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('orderBy')
            ->with('message.updated_at')
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

    private function getRepository(Builder $builder): DatabaseMessageRepository
    {
        $connection = $this->getConnection($builder);
        $logger = new NullLogger();

        return new DatabaseMessageRepository($connection, $logger);
    }
}
