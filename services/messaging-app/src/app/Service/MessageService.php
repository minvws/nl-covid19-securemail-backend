<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use MinVWS\MessagingApp\Exception\MessageNotAuthorisedException;
use MinVWS\MessagingApp\Model\Message;
use MinVWS\MessagingApp\Repository\AliasRepository;
use MinVWS\MessagingApp\Repository\EntityNotFoundException;
use MinVWS\MessagingApp\Repository\MailboxRepository;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Repository\OtpCodeRepository;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class MessageService
{
    private AliasRepository $aliasRepository;
    private LoggerInterface $logger;
    private MailboxRepository $mailboxRepository;
    private MessageRepository $messageRepository;
    private OtpCodeRepository $otpCodeRepository;

    public function __construct(
        AliasRepository $aliasRepository,
        LoggerInterface $logger,
        MailboxRepository $mailboxRepository,
        MessageRepository $messageRepository,
        OtpCodeRepository $otpCodeRepository,
    ) {
        $this->aliasRepository = $aliasRepository;
        $this->logger = $logger;
        $this->mailboxRepository = $mailboxRepository;
        $this->messageRepository = $messageRepository;
        $this->otpCodeRepository = $otpCodeRepository;
    }

    /**
     * @return Collection<Message>
     */
    public function get(?string $pseudoBsn, ?string $aliasUuid): Collection
    {
        $messages = new Collection();

        if ($pseudoBsn !== null) {
            $messages = $messages->merge($this->getMessagesByPseudoBsn($pseudoBsn));
        }
        if ($aliasUuid !== null) {
            $messages = $messages->merge($this->getMessagesByAliasUuid($aliasUuid));
        }

        return $messages;
    }

    /**
     * @return Collection<Message>
     */
    public function getMessagesByPseudoBsn(string $pseudoBsn): Collection
    {
        $messages = new Collection();
        $this->logger->debug('pseudoBsn provided, looking up messages', ['pseudoBsn' => $pseudoBsn]);

        try {
            return $this->messageRepository->getMessagesByPseudoBsn($pseudoBsn);
        } catch (RepositoryException) {
            // skip
        }
        $this->logger->debug('pseudoBsn lookup results', ['messageCount' => $messages->count()]);

        return $messages;
    }

    /**
     * @throws MessageNotAuthorisedException
     * @throws RepositoryException
     * @throws EntityNotFoundException
     */
    public function getMessageForPseudoBsn(string $uuid, string $pseudoBsn): Message
    {
        $this->logger->debug('Looking up message', ['uuid' => $uuid, 'pseudoBsn' => $pseudoBsn]);

        $message = $this->messageRepository->getByUuid($uuid);
        if ($message->mailboxUuid === null) {
            throw new MessageNotAuthorisedException('Given Digid account is not authorised to retrieve message', 403);
        }

        $mailbox = $this->mailboxRepository->getByUuid($message->mailboxUuid);
        if ($mailbox->pseudoBsn !== $pseudoBsn) {
            throw new MessageNotAuthorisedException('Given Digid account is not authorised to retrieve message', 403);
        }
        
        return $message;
    }

    /**
     * @return Collection<Message>
     */
    public function getMessagesByAliasUuid(string $aliasUuid): Collection
    {
        $messages = new Collection();
        $this->logger->debug('aliasUuid provided, looking up messages', ['aliasUuid' => $aliasUuid]);

        try {
            $alias = $this->aliasRepository->getByUuid($aliasUuid);
            $messages = $messages->merge(
                $this->messageRepository->getByAliasUuid($alias->uuid)
            );
        } catch (RepositoryException) {
            //skip
        }
        $this->logger->debug('aliasUuid lookup results', ['messageCount' => $messages->count()]);

        return $messages;
    }

    /**
     * @throws MessageNotAuthorisedException
     */
    public function getMessageForOtpCode(string $messageUuid, string $otpCodeUuid): Message
    {
        $this->logger->debug('Looking up message', ['messageUuid' => $messageUuid, 'otpCodeUuid' => $otpCodeUuid]);

        try {
            $otpCode = $this->otpCodeRepository->getByUuid($otpCodeUuid);
            if ($otpCode->messageUuid === null) {
                throw new MessageNotAuthorisedException('Given otpCodeUuid has no attached message', 403);
            }

            $messageForOtpCode = $this->messageRepository->getByUuid($otpCode->messageUuid);
        } catch (RepositoryException $repositoryException) {
            $this->logger->debug('retrieving otp or message failed', ['message' => $repositoryException->getMessage()]);
            throw new MessageNotAuthorisedException('Given otpCodeUuid is not authorised to retrieve message', 403);
        }

        if ($otpCode->messageUuid === $messageUuid) {
            return $messageForOtpCode;
        }

        try {
            if ($messageForOtpCode->aliasUuid === null) {
                throw new MessageNotAuthorisedException('Given message for OtpCode has no attached alias', 403);
            }

            $aliasForOtpCode = $this->aliasRepository->getByUuid($messageForOtpCode->aliasUuid);
            $message = $this->messageRepository->getByUuid($messageUuid);
        } catch (RepositoryException $repositoryException) {
            $this->logger->debug('retrieving alias or message failed', [
                'message' => $repositoryException->getMessage(),
            ]);
            throw new MessageNotAuthorisedException('Given otpCodeUuid is not authorised to retrieve message', 403);
        }

        if ($message->aliasUuid === $aliasForOtpCode->uuid) {
            return $message;
        }

        $this->logger->debug('given otp is not authorized', [
            'messageUuid' => $messageUuid,
            'otpCodeUuid' => $otpCodeUuid,
        ]);
        throw new MessageNotAuthorisedException('Given otpCodeUuid is not authorised to retrieve message', 403);
    }

    /**
     * @throws RepositoryException
     */
    public function markRead(Message $message): void
    {
        if (!$message->isRead()) {
            $message->firstReadAt = CarbonImmutable::now();
            $this->messageRepository->save($message);
        }
    }

    /**
     * @throws RepositoryException
     */
    public function markOtpIncorrectPhoneByMessageUuid(string $messageUuid): void
    {
        $message = $this->messageRepository->getByUuid($messageUuid);

        if ($message->otpIncorrectPhoneAt === null) {
            $message->otpIncorrectPhoneAt = CarbonImmutable::now();
            $this->messageRepository->save($message);
        }
    }
}
