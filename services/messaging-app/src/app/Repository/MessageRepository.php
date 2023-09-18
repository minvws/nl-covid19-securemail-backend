<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Repository;

use Illuminate\Support\Collection;
use MinVWS\MessagingApp\Model\Message;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

interface MessageRepository
{
    public function isHealthy(): bool;

    /**
     * @throws RepositoryException
     */
    public function delete(string $uuid): void;

    /**
     * @throws RepositoryException
     */
    public function deleteExpired(): void;

    /**
     * @return Collection<Message>
     *
     * @throws RepositoryException
     */
    public function getByAliasUuid(string $aliasUuid): Collection;

    /**
     * @return Collection<Message>
     *
     * @throws RepositoryException
     */
    public function getMessagesByPseudoBsn(string $pseudoBsn): Collection;

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByUuid(string $uuid): Message;

    /**
     * @throws RepositoryException
     */
    public function save(Message $message): void;

    /**
     * @throws RepositoryException
     */
    public function updateMailboxUuidByAliasUuid(?string $mailboxUuid, string $aliasUuid): void;
}
