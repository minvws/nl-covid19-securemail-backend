<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Model;

use Carbon\CarbonImmutable;

class GetMessage
{
    public function __construct(
        public readonly string $uuid,
        public readonly ?CarbonImmutable $notificationSentAt,
        public readonly ?CarbonImmutable $receivedAt,
        public readonly ?CarbonImmutable $bouncedAt,
        public readonly ?CarbonImmutable $otpAuthFailedAt,
        public readonly ?CarbonImmutable $otpIncorrectPhoneAt,
        public readonly ?CarbonImmutable $digidAuthFailedAt,
        public readonly ?CarbonImmutable $firstReadAt,
        public readonly ?CarbonImmutable $revokedAt,
        public readonly ?CarbonImmutable $expiredAt,
    ) {
    }
}
