<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Repository;

use MinVWS\MessagingApp\Model\PairingCode;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

interface PairingCodeRepository
{
    /**
     * @throws RepositoryException
     */
    public function deleteExpired(): void;

    /**
     * @throws RepositoryException
     */
    public function deleteByMailboxUuid(string $mailboxUuid): void;

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByEmailAddressAndCode(string $emailAddress, string $code): PairingCode;

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByMessageUuid(string $messageUuid): PairingCode;

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByUuid(string $uuid): PairingCode;

    /**
     * @throws RepositoryException
     */
    public function save(PairingCode $pairingCode): void;
}
