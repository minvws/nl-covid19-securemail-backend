<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service\OtpCode\Sms;

use Laminas\Config\Config;
use MinVWS\MessagingApp\Model\OtpCode;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Service\OtpCode\OtpCodeTypeService;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function sprintf;

class SmsService implements OtpCodeTypeService
{
    public function __construct(
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        private readonly MessageRepository $messageRepository,
        private readonly SmsInterface $sms,
    ) {
    }

    /**
     * @throws SmsException
     */
    public function sendOtpCode(OtpCode $otpCode): void
    {
        if ($otpCode->messageUuid === null) {
            throw new SmsException('otp_code has no attached message');
        }

        try {
            $message = $this->messageRepository->getByUuid($otpCode->messageUuid);
        } catch (RepositoryException $repositoryException) {
            throw SmsException::fromThrowable($repositoryException);
        }

        $body = sprintf('Uw verificatiecode is: %s', $otpCode->code);
        $phoneNumber = $message->phoneNumber;
        $senderReference = $otpCode->uuid;
        $senderName = $this->config->get('sms')->get('sender_name');

        $this->logger->debug('sending message to phoneNumber using sms', [
            'body' => $body,
            'phoneNumber' => $phoneNumber,
            'senderReference' => $senderReference,
            'senderName' => $senderName,
        ]);

        $message = new SmsMessage(
            $body,
            $phoneNumber,
            $senderName,
            $senderReference
        );
        $this->sms->send($message);
    }
}
