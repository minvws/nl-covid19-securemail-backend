<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Resource;

use MinVWS\MessagingApp\Helpers\DataObfuscator;
use MinVWS\MessagingApp\Model\Message;
use MinVWS\MessagingApp\Repository\MailboxRepository;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class MessageAuthenticationPropertiesResource extends AbstractResource
{
    public function __construct(
        private readonly MailboxRepository $mailboxRepository,
    ) {
    }

    /**
     * @throws ResourceException
     */
    public function convert(Message $message): array
    {
        $mailboxUuid = $message->mailboxUuid;

        try {
            if ($mailboxUuid !== null) {
                $mailbox = $this->mailboxRepository->getByUuid($mailboxUuid);
                $hasIdentity = $mailbox->pseudoBsn !== null;
            } else {
                $hasIdentity = false;
            }
        } catch (RepositoryException $repositoryException) {
            throw ResourceException::fromThrowable($repositoryException);
        }

        $phoneNumber = $message->phoneNumber;
        return [
            'uuid' => $message->uuid,
            'identityRequired' => $message->identityRequired,
            'hasIdentity' => $hasIdentity,
            'phoneNumber' => ($phoneNumber !== null) ? DataObfuscator::obfuscatePhoneNumber($phoneNumber) : null,
        ];
    }
}
