<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PairingCodeException;
use App\Exceptions\PairingCodeInvalidException;
use App\Models\PairingCode;
use App\Repositories\PairingCodeRepository;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class PairingCodeService
{
    public function __construct(
        private readonly PairingCodeRepository $pairingCodeRepository,
    ) {
    }

    /**
     * @throws PairingCodeInvalidException
     */
    public function getByUuid(string $uuid): PairingCode
    {
        try {
            return $this->pairingCodeRepository->getByUuid($uuid);
        } catch (RepositoryException $repositoryException) {
            throw PairingCodeInvalidException::fromThrowable($repositoryException);
        }
    }

    /**
     * @throws PairingCodeInvalidException
     */
    public function getByEmailAddress(string $emailAddress, string $pairingCode): PairingCode
    {
        try {
            return $this->pairingCodeRepository->getByEmailAddressAndPairingCode($emailAddress, $pairingCode);
        } catch (RepositoryException $repositoryException) {
            throw PairingCodeInvalidException::fromThrowable($repositoryException);
        }
    }

    /**
     * @throws PairingCodeException
     */
    public function renew(string $pairingCodeUuid): void
    {
        try {
            $this->pairingCodeRepository->renew($pairingCodeUuid);
        } catch (RepositoryException $repositoryException) {
            throw PairingCodeInvalidException::fromThrowable($repositoryException);
        }
    }
}
