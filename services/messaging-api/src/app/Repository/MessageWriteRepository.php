<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Repository;

use MinVWS\MessagingApi\Model\SaveMessage;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

interface MessageWriteRepository
{
    public function isHealthy(): bool;

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function delete(string $messageUuid): void;

    /**
     * @throws RepositoryException
     */
    public function save(SaveMessage $message): void;
}
