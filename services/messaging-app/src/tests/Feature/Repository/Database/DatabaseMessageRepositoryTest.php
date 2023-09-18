<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Repository\Database;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Encryption\Encrypter;
use MinVWS\DBCO\Encryption\Security\EncryptionHelper;
use MinVWS\MessagingApp\Helpers\HashHelper;
use MinVWS\MessagingApp\Repository\Database\DatabaseMessageRepository;
use MinVWS\MessagingApp\Repository\EntityNotFoundException;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;
use Mockery\MockInterface;
use Psr\Log\NullLogger;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use SodiumException;

use function sprintf;

class DatabaseMessageRepositoryTest extends FeatureTestCase
{
    protected DatabaseMessageRepository $messageRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageRepository = $this->getContainer()->get(DatabaseMessageRepository::class);
    }

    public function testDelete(): void
    {
        $message = $this->createMessage();

        $this->messageRepository->delete($message->uuid);

        $this->assertDatabaseCount('message', ['uuid' => $message->uuid], 0);
    }

    public function testDeleteExpired(): void
    {
        $message = $this->createMessage(['expiresAt' => $this->faker->dateTime('-1 day')]);

        $this->messageRepository->deleteExpired();

        $this->assertDatabaseCount('message', ['uuid' => $message->uuid], 0);
    }

    public function testDeleteExpiredMessageNoExpiredAt(): void
    {
        $message = $this->createMessage(['expiresAt' => null]);

        $this->messageRepository->deleteExpired();

        $databaseResults = $this->assertDatabaseCount('message', ['uuid' => $message->uuid], 1);

        $this->assertEquals($message->uuid, $databaseResults[0]->uuid);
    }

    public function testDeleteExpiredMessageWithoutAliasAndMessagebox(): void
    {
        $alias = $this->createAlias(['mailboxUuid' => null]);
        $message = $this->createMessage([
            'aliasUUid' => $alias->uuid,
            'mailboxUuid' => null,
            'expiresAt' => null,
        ]);

        $this->databaseConnection->delete(
            sprintf("DELETE FROM `alias` WHERE `uuid` = '%s'", $message->aliasUuid)
        );

        $this->messageRepository->deleteExpired();

        $this->assertDatabaseCount('message', ['uuid' => $message->uuid], 0);
    }

    public function testGetByAliasUuid(): void
    {
        $this->createMessage();
        $this->createMessage();
        $this->createMessage();
        $message = $this->createMessage(['expiresAt' => null]);

        $databaseResults = $this->messageRepository->getByAliasUuid($message->aliasUuid);
        $this->assertCount(1, $databaseResults);
        $this->assertEquals($message, $databaseResults->first());
    }

    public function testGetByPseudoBsn(): void
    {
        $pseudoBsn = $this->faker->uuid;

        $mailbox1 = $this->createMailbox();
        $mailbox2 = $this->createMailbox([
            'pseudoBsn' => $pseudoBsn,
        ]);

        $this->createMessage(['mailboxUuid' => $mailbox1->uuid]);
        $this->createMessage(['mailboxUuid' => $mailbox1->uuid]);
        $message = $this->createMessage([
            'mailboxUuid' => $mailbox2->uuid,
            'expiresAt' => null,
        ]);

        $databaseResults = $this->messageRepository->getMessagesByPseudoBsn($pseudoBsn);
        $this->assertCount(1, $databaseResults);
        $this->assertEquals($message, $databaseResults->first());
    }

    public function testGetByUuid(): void
    {
        $message = $this->createMessage(['expiresAt' => null]);

        $databaseResult = $this->messageRepository->getByUuid($message->uuid);

        $this->assertEquals($message->uuid, $databaseResult->uuid);
        $this->assertEquals($message->aliasUuid, $databaseResult->aliasUuid);
        $this->assertEquals($message->fromEmail, $databaseResult->fromEmail);
    }

    public function testGetByUuidNotExpired(): void
    {
        $message = $this->createMessage([
            'expiresAt' => CarbonImmutable::now()->addWeek()->startOfDay(),
        ]);

        $databaseResult = $this->messageRepository->getByUuid($message->uuid);

        $this->assertEquals($message, $databaseResult);
    }

    public function testGetByUuidWithExpired(): void
    {
        $alias = $this->createAlias();
        $message = $this->createMessage([
            'aliasUUid' => $alias->uuid,
            'expiresAt' => CarbonImmutable::now()->subWeek()->startOfDay(),
        ]);

        $this->expectException(EntityNotFoundException::class);
        $this->messageRepository->getByUuid($message->uuid);
    }

    public function testGetByUuidWithDecryptionFailure(): void
    {
        $alias = $this->createAlias([
            'expiresAt' => CarbonImmutable::now()->addDay(),
        ]);
        $message = $this->createMessage([
            'aliasUuid' => $alias->uuid,
            'expiresAt' => CarbonImmutable::now()->addDay(),
            'attachmentsEncryptionKey' => Encrypter::generateKey('aes-128-cbc'),
        ]);

        /** @var EncryptionHelper|MockInterface $encryptionHelper */
        $encryptionHelper = $this->mock(EncryptionHelper::class);
        $encryptionHelper->expects($this->once())
            ->method('unsealStoreValue')
            ->willThrowException(new SodiumException('decrypt failed'));

        $messageRepository = new DatabaseMessageRepository(
            $this->getContainer()->get(ConnectionInterface::class),
            $encryptionHelper,
            $this->getContainer()->get(HashHelper::class),
            new NullLogger(),
        );

        $this->expectException(RepositoryException::class);
        $messageRepository->getByUuid($message->uuid);
    }

    public function testGetByAliasUuidWithDecryptionFailure(): void
    {
        $alias = $this->createAlias([
            'expiresAt' => CarbonImmutable::now()->addDay(),
        ]);
        $this->createMessage([
            'aliasUuid' => $alias->uuid,
            'expiresAt' => CarbonImmutable::now()->addDay(),
        ]);

        /** @var EncryptionHelper|MockInterface $encryptionHelper */
        $encryptionHelper = $this->mock(EncryptionHelper::class);
        $encryptionHelper->expects($this->once())
            ->method('unsealStoreValue')
            ->willThrowException(new SodiumException('decrypt failed'));

        $messageRepository = new DatabaseMessageRepository(
            $this->getContainer()->get(ConnectionInterface::class),
            $encryptionHelper,
            $this->getContainer()->get(HashHelper::class),
            new NullLogger(),
        );

        $this->assertCount(0, $messageRepository->getByAliasUuid($alias->uuid));
    }
}
