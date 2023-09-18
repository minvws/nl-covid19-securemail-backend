<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Message;
use App\Models\MessagePreview;
use App\Models\User;
use App\Repositories\MessageRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class MessageService
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly MessageRepository $messageRepository,
    ) {
    }

    /**
     * @throws RepositoryException
     */
    public function getMessage(string $messageUuid): Message
    {
        return $this->messageRepository->getByUuid($messageUuid);
    }

    /**
     * @throws RepositoryException|AuthenticationException
     */
    public function getByUuidAndSession(string $messageUuid): Message
    {
        if (!Auth::check()) {
            throw new AuthenticationException();
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->getAuthIdentifierName() === User::AUTH_DIGID) {
            return $this->messageRepository->getByUuidAndPseudoBsn($messageUuid, $user->getAuthIdentifier());
        }

        $otpCode = $this->authenticationService->getOtpCode();
        if ($user->getAuthIdentifierName() === User::AUTH_OTP && $otpCode !== null) {
            return $this->messageRepository->getByUuidAndOtpCodeUuid($messageUuid, $otpCode->uuid);
        }

        throw new RepositoryException('no message found');
    }

    /**
     * @throws RepositoryException
     */
    public function validateAndRetrieveMessageBySessionAndOtp(string $messageUuid): Message
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user !== null && $user->getAuthIdentifierName() === User::AUTH_DIGID) {
            return $this->messageRepository->getByUuidAndPseudoBsn($messageUuid, $user->getAuthIdentifier());
        }

        $otpCode = $this->authenticationService->getOtpCode();
        if ($otpCode !== null) {
            return $this->messageRepository->getByUuidAndOtpCodeUuid($messageUuid, $otpCode->uuid);
        }

        throw new RepositoryException('No access to message');
    }

    /**
     * @throws RepositoryException
     */
    public function isPseudoBsnAllowedForMessage(string $uuid, string $pseudoBsn): bool
    {
        try {
            $this->messageRepository->getByUuidAndPseudoBsn($uuid, $pseudoBsn);
        } catch (RepositoryException $e) {
            if ($e->getCode() === 403) {
                return false;
            }
            throw RepositoryException::fromThrowable($e);
        }

        return true;
    }

    /**
     * @return Collection<MessagePreview>
     *
     * @throws RepositoryException
     */
    public function getList(): Collection
    {
        $messages = new Collection();

        if (!Auth::check()) {
            return $messages;
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->getAuthIdentifierName() === User::AUTH_DIGID) {
            $messages = $messages->merge($this->messageRepository->getByPseudoBsn($user->getAuthIdentifier()));
        }

        if ($user->getAuthIdentifierName() === User::AUTH_OTP) {
            $messages = $messages->merge($this->messageRepository->getByAliasUuid($user->getAuthIdentifier()));
        }

        return $messages;
    }
}
