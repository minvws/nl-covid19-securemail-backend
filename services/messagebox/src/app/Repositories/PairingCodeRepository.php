<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PairingCode;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

interface PairingCodeRepository
{
    public function isHealthy(): bool;

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByEmailAddressAndPairingCode(string $emailAddress, string $pairingCode): PairingCode;

    /**
     * @throws EntityExpiredException
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByUuid(string $uuid): PairingCode;

    /**
     * @throws RepositoryException
     */
    public function renew(string $pairingCodeUuid): void;
}
