<?php

declare(strict_types=1);

namespace SecureMail\Shared\Application\Repositories;

use SecureMail\Shared\Application\Exceptions\RepositoryException;
use SecureMail\Shared\Application\Models\MittensUserInfo;
use SecureMail\Shared\Application\Models\PseudoBsn;

interface PseudoBsnRepository
{
    /**
     * @throws RepositoryException
     */
    public function getByBsn(string $bsn): string;

    /**
     * @throws RepositoryException
     */
    public function getByDigidToken(string $idToken): MittensUserInfo;

    /**
     * @throws RepositoryException
     */
    public function getByToken(string $pseudoBsnToken): PseudoBsn;
}
