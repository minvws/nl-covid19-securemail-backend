<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Repository\Filesystem;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use MinVWS\MessagingApi\Model\SaveAttachment;
use MinVWS\MessagingApi\Repository\AttachmentWriteRepository;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class FilesystemAttachmentRepository implements AttachmentWriteRepository
{
    public function __construct(
        private readonly FilesystemOperator $filesystem,
    ) {
    }

    /**
     * @throws RepositoryException
     */
    public function save(SaveAttachment $attachment): void
    {
        try {
            $this->filesystem->write($attachment->uuid, $attachment->content);
        } catch (FilesystemException $filesystemException) {
            throw RepositoryException::fromThrowable($filesystemException);
        }
    }
}
