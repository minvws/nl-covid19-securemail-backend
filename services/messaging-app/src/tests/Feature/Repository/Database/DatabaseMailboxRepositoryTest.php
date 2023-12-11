<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Repository\Database;

use MinVWS\MessagingApp\Repository\Database\DatabaseMailboxRepository;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;

class DatabaseMailboxRepositoryTest extends FeatureTestCase
{
    protected DatabaseMailboxRepository $mailboxRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailboxRepository = $this->getContainer()->get(DatabaseMailboxRepository::class);
    }

    public function testDeleteExpired(): void
    {
        $mailbox = $this->createMailbox();

        $this->mailboxRepository->deleteExpired();

        $this->assertDatabaseCount('mailbox', ['uuid' => $mailbox->uuid], 0);
    }

    public function testDeleteExpiredWhenAttachedAlias(): void
    {
        $mailbox = $this->createMailbox();
        $this->createAlias(['mailboxUuid' => $mailbox->uuid]);

        $this->mailboxRepository->deleteExpired();

        $this->assertDatabaseCount('mailbox', ['uuid' => $mailbox->uuid], 1);
    }

    public function testDeleteExpiredWhenAttachedMessage(): void
    {
        $mailbox = $this->createMailbox();
        $this->createMessage(['mailboxUuid' => $mailbox->uuid]);

        $this->mailboxRepository->deleteExpired();

        $this->assertDatabaseCount('mailbox', ['uuid' => $mailbox->uuid], 1);
    }

    public function testDeleteExpiredWhenAttachedAliasAndMessage(): void
    {
        $mailbox = $this->createMailbox();
        $this->createAlias(['mailboxUuid' => $mailbox->uuid]);
        $this->createMessage(['mailboxUuid' => $mailbox->uuid]);

        $this->mailboxRepository->deleteExpired();

        $this->assertDatabaseCount('mailbox', ['uuid' => $mailbox->uuid], 1);
    }
}
