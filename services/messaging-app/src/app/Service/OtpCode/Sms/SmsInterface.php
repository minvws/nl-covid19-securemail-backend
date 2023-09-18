<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service\OtpCode\Sms;

interface SmsInterface
{
    /**
     * @retrun string The message identifier
     *
     * @throws SmsException
     */
    public function send(SmsMessage $smsMessage): string;
}
