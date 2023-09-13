<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Repository\Database;

use MinVWS\MessagingApp\Repository\Database\DatabaseAttachmentRepository;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;

class DatabaseAttachmentRepositoryTest extends FeatureTestCase
{
    protected DatabaseAttachmentRepository $attachmentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attachmentRepository = $this->getContainer()->get(DatabaseAttachmentRepository::class);
    }

    public function testDelete(): void
    {
        $attachment = $this->createAttachment();

        $this->attachmentRepository->delete($attachment);

        $this->assertDatabaseCount('attachment', ['uuid' => $attachment->uuid], 0);
    }

    public function testGetExpired(): void
    {
        $attachment = $this->createAttachment(['messageUuid' => null]);

        $attachments = $this->attachmentRepository->getExpired();

        $this->assertCount(1, $attachments);
        $this->assertEquals($attachment->uuid, $attachments->first()->uuid);
    }

    public function testGetExpiredWhenAttachmentHasMessage(): void
    {
        $this->createAttachment(['messageUuid' => $this->createMessage()->uuid]);

        $attachments = $this->attachmentRepository->getExpired();

        $this->assertCount(0, $attachments);
    }
}
