<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Service;

use Illuminate\Support\Collection;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\UnableToDeleteFile;
use MinVWS\MessagingApp\Repository\AttachmentRepository;
use MinVWS\MessagingApp\Service\AttachmentException;
use MinVWS\MessagingApp\Service\AttachmentService;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class AttachmentServiceTest extends FeatureTestCase
{
    private AttachmentService $attachmentService;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $this->container->set(FilesystemOperator::class, $this->filesystem);

        $this->attachmentService = $this->container->get(AttachmentService::class);
    }

    public function testDeleteFileFromExpiredAttachment(): void
    {
        $attachment = $this->createAttachment(['messageUuid' => null]);
        $this->filesystem->write($attachment->uuid, $this->faker->paragraph);

        $this->attachmentService->deleteExpired();

        $this->assertFalse($this->filesystem->has($attachment->uuid));
    }

    public function testDeleteFileFromExpiredAttachmentWhenFileNoLongerExists(): void
    {
        $attachment = $this->createAttachment();

        /** @var AttachmentRepository|MockObject $attachmentRepository */
        $attachmentRepository = $this->mock(AttachmentRepository::class);
        $attachmentRepository->expects($this->once())
            ->method('getExpired')
            ->willReturn(new Collection([$attachment]));

        /** @var FilesystemOperator|MockObject $filesystem */
        $filesystem = $this->mock(FilesystemOperator::class);
        $filesystem->expects($this->once())
            ->method('delete')
            ->with($attachment->uuid)
            ->willThrowException(new UnableToDeleteFile('file not found'));

        $attachmentService = new AttachmentService(
            $attachmentRepository,
            $filesystem,
            new NullLogger(),
        );

        $this->expectException(AttachmentException::class);
        $attachmentService->deleteExpired();

        $this->assertFalse($this->filesystem->has($attachment->uuid));
    }

    public function testDontDeleteFileFromNonExpiredAttachment(): void
    {
        $attachment = $this->createAttachment(['messageUuid' => $this->createMessage()->uuid]);
        $this->filesystem->write($attachment->uuid, $this->faker->paragraph);

        $this->attachmentService->deleteExpired();

        $this->assertTrue($this->filesystem->has($attachment->uuid));
    }
}
