<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Repository;

use MinVWS\MessagingApp\Model\Alias;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

interface AliasRepository
{
    /**
     * @throws RepositoryException
     */
    public function deleteExpired(): void;

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByPlatformIdentifier(string $platform, string $platformIdentifier): Alias;

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByUuid(string $uuid): Alias;

    /**
     * @throws RepositoryException
     */
    public function save(Alias $alias): void;
}
