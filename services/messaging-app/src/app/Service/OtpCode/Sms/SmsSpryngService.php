<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service\OtpCode\Sms;

use Exception;
use Laminas\Config\Config;
use Psr\Log\LoggerInterface;
use Spryng\SpryngRestApi\Objects\Message as SpryngMessage;
use Spryng\SpryngRestApi\Spryng;

class SmsSpryngService implements SmsInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        private readonly Spryng $spryng,
    ) {
    }

    /**
     * @throws SmsException
     */
    public function send(SmsMessage $smsMessage): string
    {
        $spryngMessage = new SpryngMessage();
        $spryngMessage->setRoute($this->config->get('route'));
        $spryngMessage->setBody($smsMessage->body);
        $spryngMessage->setRecipients([$smsMessage->recipient]);
        $spryngMessage->setOriginator($smsMessage->senderName);
        $spryngMessage->setReference($smsMessage->senderReference);

        $this->logger->info('sending sms using spryng', ['message' => $spryngMessage]);

        try {
            $spryngResponse = $this->spryng->message->create($spryngMessage);
            $spryngResponseBody = $spryngResponse->getRawBody();

            $this->logger->debug('spryng response', [
                'code' => $spryngResponse->getResponseCode(),
                'body' => $spryngResponseBody,
            ]);

            if ($spryngResponse->getResponseCode() === 200) {
                $message = $spryngResponse->toObject();
                return $message->getId();
            }

            throw new Exception('id not found in response');
        } catch (Exception $exception) {
            $this->logger->error('sending sms using spryng failed', [
                'exception' => $exception,
            ]);

            throw SmsException::fromThrowable($exception);
        }
    }
}
