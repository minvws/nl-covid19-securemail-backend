<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service\OtpCode;

use MinVWS\MessagingApp\Model\OtpCode;
use MinVWS\MessagingApp\Service\OtpCode\Sms\SmsException;

interface OtpCodeTypeService
{
    /**
     * @throws SmsException
     */
    public function sendOtpCode(OtpCode $otpCode);
}
