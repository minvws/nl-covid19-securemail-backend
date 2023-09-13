<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Model;

use Carbon\CarbonInterface;

class Message
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $platform,
        public readonly ?string $aliasUuid,
        public readonly ?string $mailboxUuid,
        public readonly string $fromName,
        public readonly string $fromEmail,
        public readonly string $toName,
        public readonly string $toEmail,
        public readonly ?string $phoneNumber,
        public readonly string $subject,
        public readonly string $text,
        public readonly string $footer,
        public readonly ?string $attachmentsEncryptionKey,
        public readonly ?CarbonInterface $expiresAt,
        public readonly CarbonInterface $createdAt,
        public readonly bool $identityRequired,
        public ?CarbonInterface $notificationSentAt = null,
        public ?CarbonInterface $firstReadAt = null,
        public ?CarbonInterface $otpIncorrectPhoneAt = null,
        public readonly int $attachmentCount = 0,
    ) {
    }

    public function isRead(): bool
    {
        return $this->firstReadAt !== null;
    }
}
