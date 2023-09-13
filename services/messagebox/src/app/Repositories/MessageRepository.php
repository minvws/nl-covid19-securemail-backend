<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Message;
use App\Models\MessageAuthenticationProperties;
use App\Models\MessagePreview;
use App\Models\User;
use Illuminate\Support\Collection;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

interface MessageRepository
{
    public function isHealthy(): bool;

    /**
     * @param string $messageUuid
     * @return MessageAuthenticationProperties
     *
     * @throws RepositoryException
     */
    public function getAuthenticationProperties(string $messageUuid): MessageAuthenticationProperties;

    /**
     * @throws RepositoryException
     */
    public function getByUuid(string $messageUuid): Message;

    /**
     * @throws RepositoryException
     */
    public function getByUuidAndPseudoBsn(string $uuid, string $pseudoBsn): Message;

    /**
     * @throws RepositoryException
     */
    public function getByUuidAndOtpCodeUuid(string $messageUuid, string $otpCodeUuid): Message;

    /**
     * @return Collection<MessagePreview>
     *
     * @throws RepositoryException
     */
    public function getByAliasUuid(string $aliasUuid): Collection;

    /**
     * @return Collection<MessagePreview>
     *
     * @throws RepositoryException
     */
    public function getByPseudoBsn(string $pseudoBsn): Collection;

    /**
     * @throws RepositoryException
     */
    public function linkMessageToUser(string $messageUuid, User $user): void;

    /**
     * @throws RepositoryException
     */
    public function reportIncorrectPhone(string $messageUuid): void;

    /**
     * @throws RepositoryException
     */
    public function unlinkMessageByUuid(string $messageUuid, string $reason): void;
}
