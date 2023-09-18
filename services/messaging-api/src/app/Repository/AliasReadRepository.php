<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Repository;

use Carbon\CarbonImmutable;
use MinVWS\MessagingApi\Model\GetAlias;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

interface AliasReadRepository
{
    /**
     * @throws RepositoryException
     */
    public function countStatusUpdates(CarbonImmutable $since): int;

    /**
     * @return GetAlias[]
     *
     * @throws RepositoryException
     */
    public function getStatusUpdates(CarbonImmutable $since, ?int $limit): array;
}
