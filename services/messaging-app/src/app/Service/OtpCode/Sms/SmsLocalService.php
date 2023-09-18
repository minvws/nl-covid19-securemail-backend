<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service\OtpCode\Sms;

use Psr\Log\LoggerInterface;

class SmsLocalService implements SmsInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function send(SmsMessage $smsMessage): string
    {
        $this->logger->debug('sending message using local fake interface');

        return 'sms-local';
    }
}
