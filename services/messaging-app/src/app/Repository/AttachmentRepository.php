<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Repository;

use Illuminate\Support\Collection;
use MinVWS\MessagingApp\Model\Attachment;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

interface AttachmentRepository
{
    /**
     * @throws RepositoryException
     */
    public function delete(Attachment $attachment): void;

    /**
     * @return Collection<Attachment>
     *
     * @throws RepositoryException
     */
    public function getExpired(): Collection;

    /**
     * @throws RepositoryException
     */
    public function getByUuidAndMessageUuid(string $attachmentUuid, string $messageUuid): Attachment;

    /**
     * @return Collection<Attachment>
     *
     * @throws RepositoryException
     */
    public function getByMessageUuid(string $messageUuid): Collection;

    /**
     * @throws RepositoryException
     */
    public function save(Attachment $attachment): void;
}
