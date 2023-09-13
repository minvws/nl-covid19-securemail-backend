<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service;

use Illuminate\Support\Collection;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use MinVWS\MessagingApp\Model\Attachment;
use MinVWS\MessagingApp\Repository\AttachmentRepository;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class AttachmentService
{
    public function __construct(
        private readonly AttachmentRepository $attachmentRepository,
        private readonly FilesystemOperator $filesystem,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws AttachmentException
     */
    public function deleteExpired(): void
    {
        try {
            $attachments = $this->attachmentRepository->getExpired();

            foreach ($attachments as $attachment) {
                $this->filesystem->delete($attachment->uuid);
                $this->attachmentRepository->delete($attachment);
            }
        } catch (FilesystemException | RepositoryException $exception) {
            $this->logger->error('delete expired attachments failed', ['message' => $exception->getMessage()]);
            throw AttachmentException::fromThrowable($exception);
        }
    }

    /**
     * @throws RepositoryException
     */
    public function getByUuidAndMessageUuid(string $attachmentUuid, string $messageUuid): Attachment
    {
        return $this->attachmentRepository->getByUuidAndMessageUuid($attachmentUuid, $messageUuid);
    }

    /**
     * @throws RepositoryException
     */
    public function getAttachmentsByMessageUuid(string $messageUuid): Collection
    {
        return $this->attachmentRepository->getByMessageUuid($messageUuid);
    }
}
