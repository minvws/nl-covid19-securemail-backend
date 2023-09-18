<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Service;

use MinVWS\MessagingApi\Model\SaveAttachment;
use MinVWS\MessagingApi\Repository\AttachmentWriteRepository;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class AttachmentService
{
    public function __construct(
        private readonly AttachmentWriteRepository $attachmentRepository,
    ) {
    }

    /**
     * @param SaveAttachment[] $attachments
     *
     * @throws RepositoryException
     */
    public function saveAttachments(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $this->attachmentRepository->save($attachment);
        }
    }
}
