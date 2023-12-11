<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service;

use SecureMail\Shared\Application\Exceptions\BsnServiceException;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use SecureMail\Shared\Application\Models\PseudoBsn;
use SecureMail\Shared\Application\Repositories\PseudoBsnRepository;

class BsnService
{
    public function __construct(
        private readonly PseudoBsnRepository $bsnRepository,
    ) {
    }

    /**
     * @throws BsnServiceException
     */
    public function getByToken(string $pseudoBsnToken): PseudoBsn
    {
        try {
            return $this->bsnRepository->getByToken($pseudoBsnToken);
        } catch (RepositoryException $repositoryException) {
            throw BsnServiceException::fromThrowable($repositoryException);
        }
    }
}
