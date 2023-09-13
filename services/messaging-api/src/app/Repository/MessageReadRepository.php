<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Repository;

use Carbon\CarbonImmutable;
use MinVWS\MessagingApi\Model\GetMessage;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

interface MessageReadRepository
{
    /**
     * @throws RepositoryException
     */
    public function countStatusUpdates(CarbonImmutable $since): int;

    public function isHealthy(): bool;

    /**
     * @return GetMessage[]
     *
     * @throws RepositoryException
     */
    public function getStatusUpdates(CarbonImmutable $since, ?int $limit): array;
}
