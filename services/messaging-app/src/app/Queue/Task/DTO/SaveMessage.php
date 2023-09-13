<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Queue\Task\DTO;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use MinVWS\MessagingApp\Enum\MessageType;

use function base64_decode;

class SaveMessage implements Dto
{
    public function __construct(
        public readonly string $uuid,
        public readonly MessageType $type,
        public readonly string $platform,
        public readonly string $platformIdentifier,
        public readonly ?CarbonInterface $aliasExpiresAt,
        public readonly string $fromName,
        public readonly string $fromEmail,
        public readonly string $toName,
        public readonly string $toEmail,
        public readonly ?string $phoneNumber,
        public readonly string $subject,
        public readonly string $text,
        public readonly string $footer,
        public readonly array $attachments,
        public readonly string $attachmentsEncryptionKey,
        public readonly ?CarbonInterface $expiresAt,
        public readonly bool $identityRequired,
        public readonly ?string $pseudoBsnToken,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'platform' => $this->platform,
            'platformIdentifier' => $this->platformIdentifier,
            'aliasExpiresAt' => $this->aliasExpiresAt,
            'fromName' => $this->fromName,
            'fromEmail' => $this->fromEmail,
            'toName' => $this->toName,
            'toEmail' => $this->toEmail,
            'phoneNumber' => $this->phoneNumber,
            'subject' => $this->subject,
            'text' => $this->text,
            'footer' => $this->footer,
            'attachments' => $this->attachments,
            'expiresAt' => $this->expiresAt,
        ];
    }

    public static function jsonDeserialize(array $data): self
    {
        /** @var MessageType $messageType */
        $messageType = MessageType::from($data['type']);

        return new self(
            $data['uuid'],
            $messageType,
            $data['platform'],
            $data['platformIdentifier'],
            $data['aliasExpiresAt'] !== null ? CarbonImmutable::createFromFormat('c', $data['aliasExpiresAt']) : null,
            $data['fromName'],
            $data['fromEmail'],
            $data['toName'],
            $data['toEmail'],
            $data['phoneNumber'],
            $data['subject'],
            $data['text'],
            $data['footer'],
            $data['attachments'],
            base64_decode($data['attachmentsEncryptionKey']),
            $data['expiresAt'] !== null ? CarbonImmutable::createFromFormat('c', $data['expiresAt']) : null,
            $data['identityRequired'],
            $data['pseudoBsnToken'],
        );
    }
}
