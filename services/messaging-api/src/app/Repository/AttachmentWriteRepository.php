<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Repository;

use MinVWS\MessagingApi\Model\SaveAttachment;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

interface AttachmentWriteRepository
{
    /**
     * @throws RepositoryException
     */
    public function save(SaveAttachment $attachment): void;
}
