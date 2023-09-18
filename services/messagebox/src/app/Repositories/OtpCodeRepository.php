<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Enums\LoginType;
use App\Models\OtpCode;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

interface OtpCodeRepository
{
    public function isHealthy(): bool;

    /**
     * @throws EntityExpiredException
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function requestByTypeAndMessageUuid(LoginType $loginType, string $messageUuid): OtpCode;

    /**
     * @throws EntityExpiredException
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByMessageUuidAndOtpCode(string $messageUuid, string $otpCode): string;
}
