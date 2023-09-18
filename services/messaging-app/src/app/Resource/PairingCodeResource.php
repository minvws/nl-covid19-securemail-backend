<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Resource;

use MinVWS\MessagingApp\Helpers\DataObfuscator;
use MinVWS\MessagingApp\Model\Message;
use MinVWS\MessagingApp\Model\PairingCode;

class PairingCodeResource extends AbstractResource
{
    public function convert(PairingCode $pairingCode, Message $message): array
    {
        return [
            'uuid' => $pairingCode->uuid,
            'messageUuid' => $message->uuid,
            'emailAddress' => DataObfuscator::obfuscateEmailAddress($message->toEmail),
            'toName' => $message->toName,
            'validUntil' => $this->convertDate($pairingCode->validUntil),
        ];
    }
}
