<?php

declare(strict_types=1);

namespace App\Repositories\Bridge;

use App\Models\Attachment;
use App\Models\Message;
use App\Models\MessageAuthenticationProperties;
use App\Models\MessagePreview;
use App\Models\User;
use App\Repositories\MessageRepository;
use Closure;
use Illuminate\Support\Collection;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function base64_decode;

class BridgeMessageRepository extends BridgeRepository implements MessageRepository
{
    /**
     * @throws BridgeRequestException
     * @throws RepositoryException
     */
    public function getAuthenticationProperties(string $messageUuid): MessageAuthenticationProperties
    {
        $response = $this->request('messages-authentication-properties', [], [
            'messageUuid' => $messageUuid,
        ]);

        return $this->convertToMessageAuthenticationProperties($response);
    }

    /**
     * @return Collection<MessagePreview>
     *
     * @throws RepositoryException
     */
    public function getByAliasUuid(string $aliasUuid): Collection
    {
        $response = $this->request('messages', [], [], [
            'aliasUuid' => $aliasUuid,
        ]);

        $collection = new Collection($response);

        return $collection->map(Closure::fromCallable([$this, 'convertToMessagePreview']));
    }

    /**
     * @return Collection<MessagePreview>
     *
     * @throws RepositoryException
     */
    public function getByPseudoBsn(string $pseudoBsn): Collection
    {
        $response = $this->request('messages', [], [], [
            'pseudoBsn' => $pseudoBsn,
        ]);

        $collection = new Collection($response);

        return $collection->map(Closure::fromCallable([$this, 'convertToMessagePreview']));
    }

    /**
     * @throws RepositoryException
     */
    public function getByUuid(string $messageUuid): Message
    {
        $response = $this->request('messages-by-uuid', [], [
            'uuid' => $messageUuid,
        ]);

        return $this->convertToMessage($response);
    }

    /**
     * @throws RepositoryException
     */
    public function getByUuidAndPseudoBsn(string $uuid, string $pseudoBsn): Message
    {
        try {
            $response = $this->request('messages-by-uuid', [], ['uuid' => $uuid], ['pseudoBsn' => $pseudoBsn]);

            return $this->convertToMessage($response);
        } catch (BridgeRequestException $bridgeRequestException) {
            throw RepositoryException::fromThrowable($bridgeRequestException);
        }
    }

    /**
     * @throws RepositoryException
     */
    public function getByUuidAndOtpCodeUuid(string $messageUuid, string $otpCodeUuid): Message
    {
        try {
            $response = $this->request(
                'messages-by-uuid',
                [],
                ['uuid' => $messageUuid],
                ['otpCodeUuid' => $otpCodeUuid],
            );

            return $this->convertToMessage($response);
        } catch (BridgeRequestException $bridgeRequestException) {
            throw RepositoryException::fromThrowable($bridgeRequestException);
        }
    }

    /**
     * @throws RepositoryException
     */
    public function linkMessageToUser(string $messageUuid, User $user): void
    {
        try {
            $this->request('messages-link', [
                'mailboxUuid' => $user->getAuthIdentifier(),
                'messageUuid' => $messageUuid,
            ]);
        } catch (BridgeRequestException $bridgeRequestException) {
            throw RepositoryException::fromThrowable($bridgeRequestException);
        }
    }

    /**
     * @throws RepositoryException
     */
    public function reportIncorrectPhone(string $messageUuid): void
    {
        try {
            $this->request('messages-incorrect-phone', [
                'messageUuid' => $messageUuid,
            ]);
        } catch (BridgeRequestException $bridgeRequestException) {
            throw RepositoryException::fromThrowable($bridgeRequestException);
        }
    }

    public function unlinkMessageByUuid(string $messageUuid, string $reason): void
    {
        try {
            $this->request('messages-unlink', [
                'messageUuid' => $messageUuid,
                'reason' => $reason,
            ]);
        } catch (BridgeRequestException $bridgeRequestException) {
            throw RepositoryException::fromThrowable($bridgeRequestException);
        }
    }

    private function convertToMessage(object $response): Message
    {
        return new Message(
            $response->uuid,
            $response->aliasUuid,
            $response->fromName,
            $response->toName,
            $response->subject,
            $response->text,
            $response->footer,
            $this->convertDate($response->createdAt),
            $response->expiresAt !== null ? $this->convertDate($response->expiresAt) : null,
            $this->convertAttachments($response->attachments),
            $response->attachmentsEncryptionKey !== null ? base64_decode($response->attachmentsEncryptionKey) : null,
        );
    }

    private function convertToMessagePreview(object $response): MessagePreview
    {
        return new MessagePreview(
            $response->uuid,
            $response->fromName,
            $response->subject,
            $this->convertDate($response->createdAt),
            $response->isRead,
            $response->hasAttachments,
        );
    }

    private function convertToMessageAuthenticationProperties(object $response): MessageAuthenticationProperties
    {
        return new MessageAuthenticationProperties(
            $response->uuid,
            $response->identityRequired,
            $response->hasIdentity,
            $response->phoneNumber,
        );
    }

    public function convertAttachments(array $attachments): Collection
    {
        $collection = new Collection($attachments);

        return $collection->map(function (object $attachment) {
            return new Attachment(
                $attachment->uuid,
                $attachment->name,
                $attachment->mime_type,
            );
        });
    }
}
