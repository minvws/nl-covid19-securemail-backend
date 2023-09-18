<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Resource;

use Carbon\CarbonInterface;
use MinVWS\MessagingApi\Model\GetMessage;

use function array_map;

class MessageResource
{
    public function convert(GetMessage $message): array
    {
        return [
            'messageUuid' => $message->uuid,
            'notificationSentAt' => $this->convertDate($message->notificationSentAt),
            'receivedAt' => $this->convertDate($message->receivedAt),
            'bouncedAt' => $this->convertDate($message->bouncedAt),
            'otpAuthFailedAt' => $this->convertDate($message->otpAuthFailedAt),
            'otpIncorrectPhoneAt' => $this->convertDate($message->otpIncorrectPhoneAt),
            'digidAuthFailedAt' => $this->convertDate($message->digidAuthFailedAt),
            'firstReadAt' => $this->convertDate($message->firstReadAt),
            'revokedAt' => $this->convertDate($message->revokedAt),
            'expiredAt' => $this->convertDate($message->expiredAt),
        ];
    }

    /**
     * @param GetMessage[] $messsages
     */
    public function convertCollection(array $messsages): array
    {
        return array_map([$this, 'convert'], $messsages);
    }

    private function convertDate(?CarbonInterface $date): ?string
    {
        return $date?->format('c');
    }
}
