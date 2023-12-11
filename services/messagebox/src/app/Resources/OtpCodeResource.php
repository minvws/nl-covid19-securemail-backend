<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\OtpCode;

class OtpCodeResource extends Resource
{
    public function convertToResource(OtpCode $otpCode): object
    {
        $otpCodeResource = [
            'uuid' => $otpCode->uuid,
            'phoneNumber' => $otpCode->phoneNumber,
            'loginType' => $otpCode->loginType->value,
            'validUntil' => $this->formatDate($otpCode->validUntil),
        ];

        return (object) $otpCodeResource;
    }
}
