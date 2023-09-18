<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attachment;
use App\Repositories\AttachmentRepository;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class AttachmentService
{
    public function __construct(
        private readonly AttachmentRepository $attachmentRepository,
    ) {
    }

    /**
     * @throws RepositoryException
     */
    public function getAttachment(string $attachmentUuid, string $messageUuid): Attachment
    {
        return $this->attachmentRepository->getByAttachmentUuidAndMessageUuid($attachmentUuid, $messageUuid);
    }
}
