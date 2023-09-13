<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service\OtpCode\Sms;

class SmsMessage
{
    public function __construct(
        public readonly string $body,
        public readonly string $recipient,
        public readonly string $senderName,
        public readonly string $senderReference,
    ) {
    }
}
