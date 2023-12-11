<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Resource;

use MinVWS\MessagingApp\Helpers\DataObfuscator;
use MinVWS\MessagingApp\Model\OtpCode;

class OtpCodeResource extends AbstractResource
{
    public function convert(OtpCode $otpCode, string $phoneNumber): array
    {
        return [
            'uuid' => $otpCode->uuid,
            'type' => $otpCode->type,
            'phoneNumber' => DataObfuscator::obfuscatePhoneNumber($phoneNumber),
            'validUntil' => $this->convertDate($otpCode->validUntil),
        ];
    }
}
