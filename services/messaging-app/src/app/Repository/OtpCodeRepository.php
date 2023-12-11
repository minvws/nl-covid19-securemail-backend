<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Repository;

use MinVWS\MessagingApp\Model\OtpCode;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

interface OtpCodeRepository
{
    /**
     * @throws RepositoryException
     */
    public function delete(OtpCode $otpCode): void;

    /**
     * @throws RepositoryException
     */
    public function deleteExpired(): void;

    /**
     * @throws RepositoryException
     */
    public function getByMessageUuidAndCode(string $messageUuid, string $code): OtpCode;

    /**
     * @throws RepositoryException
     * @returns array<OtpCode>
     *
     */
    public function getByMessageUuid(string $messageUuid): array;

    /**
     * @throws RepositoryException
     */
    public function getByUuid(string $uuid): OtpCode;

    /**
     * @throws RepositoryException
     */
    public function save(OtpCode $otpCode): void;
}
