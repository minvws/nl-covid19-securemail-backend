<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Attachment;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

interface AttachmentRepository
{
    /**
     * @throws RepositoryException
     */
    public function getByAttachmentUuidAndMessageUuid(string $attachmentUuid, string $messageUuid): Attachment;
}
