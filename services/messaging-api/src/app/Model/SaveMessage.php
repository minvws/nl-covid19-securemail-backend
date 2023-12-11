<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Model;

use Carbon\CarbonInterface;
use MinVWS\MessagingApi\Enum\MessageType;

class SaveMessage
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
}
