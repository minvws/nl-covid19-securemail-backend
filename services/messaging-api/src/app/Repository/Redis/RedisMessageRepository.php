<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Repository\Redis;

use Exception;
use MinVWS\MessagingApi\Model\SaveMessage;
use MinVWS\MessagingApi\Repository\MessageWriteRepository;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function base64_encode;
use function json_encode;

class RedisMessageRepository extends RedisRepository implements MessageWriteRepository
{
    /**
     * @throws RepositoryException
     */
    public function delete(string $messageUuid): void
    {
        try {
            $this->client->rpush($this->config->get('redis')->get('lists')->get('message_delete'), [
                json_encode(['uuid' => $messageUuid]),
            ]);
        } catch (Exception $exception) {
            throw RepositoryException::fromThrowable($exception);
        }
    }

    /**
     * @throws RepositoryException
     */
    public function save(SaveMessage $message): void
    {
        try {
            $this->client->rpush($this->config->get('redis')->get('lists')->get('message_save'), [
                $this->prepareSaveMessageForTransport($message),
            ]);
            $this->logger->info('saved message', ['uuid' => $message->uuid]);
        } catch (Exception $exception) {
            throw RepositoryException::fromThrowable($exception);
        }
    }

    private function prepareSaveMessageForTransport(SaveMessage $message): string
    {
        $aliasExpiresAt = $message->aliasExpiresAt?->format('c');
        $expiresAt = $message->expiresAt?->format('c');

        return json_encode([
            'uuid' => $message->uuid,
            'type' => $message->type,
            'platform' => $message->platform,
            'platformIdentifier' => $message->platformIdentifier,
            'aliasExpiresAt' => $aliasExpiresAt,
            'fromName' => $message->fromName,
            'fromEmail' => $message->fromEmail,
            'toName' => $message->toName,
            'toEmail' => $message->toEmail,
            'phoneNumber' => $message->phoneNumber,
            'subject' => $message->subject,
            'text' => $message->text,
            'footer' => $message->footer,
            'attachments' => $message->attachments,
            'attachmentsEncryptionKey' => base64_encode($message->attachmentsEncryptionKey),
            'expiresAt' => $expiresAt,
            'identityRequired' => $message->identityRequired,
            'pseudoBsnToken' => $message->pseudoBsnToken,
        ]);
    }
}
