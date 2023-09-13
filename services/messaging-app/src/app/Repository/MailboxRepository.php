<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Repository;

use MinVWS\MessagingApp\Model\Mailbox;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

interface MailboxRepository
{
    /**
     * @throws RepositoryException
     */
    public function deleteExpired(): void;

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByPseudoBsn(string $pseudoBsn): Mailbox;

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByUuid(string $uuid): Mailbox;

    /**
     * @throws RepositoryException
     */
    public function save(Mailbox $mailbox): void;
}
