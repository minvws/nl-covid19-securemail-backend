<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Queue\Task;

use Carbon\CarbonImmutable;
use Exception;
use League\CommonMark\MarkdownConverterInterface;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Helpers\PHPDocHelper;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\Audit\Models\AuditObject;
use MinVWS\MessagingApp\Enum\MessageType;
use MinVWS\MessagingApp\Enum\QueueList;
use MinVWS\MessagingApp\Model\Alias;
use MinVWS\MessagingApp\Model\Attachment;
use MinVWS\MessagingApp\Model\Mailbox;
use MinVWS\MessagingApp\Model\Message;
use MinVWS\MessagingApp\Queue\QueueClient;
use MinVWS\MessagingApp\Queue\QueueException;
use MinVWS\MessagingApp\Repository\AliasRepository;
use MinVWS\MessagingApp\Repository\AttachmentRepository;
use MinVWS\MessagingApp\Repository\EntityNotFoundException;
use MinVWS\MessagingApp\Repository\MailboxRepository;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Service\BsnService;
use MinVWS\MessagingApp\Service\TwigTemplateService;
use MinVWS\MessagingApp\Service\UuidService;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\BsnServiceException;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class MessageSaveProcessor implements TaskProcessor
{
    public function __construct(
        private readonly AliasRepository $aliasRepository,
        private readonly AttachmentRepository $attachmentRepository,
        private readonly AuditService $auditService,
        private readonly LoggerInterface $logger,
        private readonly MailboxRepository $mailboxRepository,
        private readonly MarkdownConverterInterface $markdownConverter,
        private readonly MessageRepository $messageRepository,
        private readonly QueueClient $queueClient,
        private readonly TwigTemplateService $templateService,
        private readonly BsnService $bsnService,
    ) {
    }

    /**
     * @auditEventDescription Verwerk inkomend bericht
     *
     * @throws Exception
     * @throws QueueException
     */
    public function process(DTO\Dto $task): void
    {
        if (!$task instanceof DTO\SaveMessage) {
            throw new QueueException('task is not of type message_save');
        }

        $this->logger->info('process message (save)', ['messageUuid' => $task->uuid]);

        $this->auditService->registerEvent(
            AuditEvent::create(
                __METHOD__,
                AuditEvent::ACTION_EXECUTE,
                PHPDocHelper::getTagAuditEventDescriptionByActionName(__METHOD__),
            )
            ->object(AuditObject::create('message', $task->uuid)->detail('type', $task->type)),
            function (AuditEvent $auditEvent) use ($task) {
                $messageType = $task->type;
                switch ($messageType) {
                    case $messageType->equals(MessageType::DIRECT()):
                        $this->sendDirect($task);
                        break;
                    case $messageType->equals(MessageType::SECURE()):
                        $this->sendSecure($task);
                        break;
                    default:
                        $this->logger->error('invalid type for task', ['type' => $messageType]);
                        break;
                }
            }
        );

        $this->auditService->finalizeEvent();
    }

    private function sendDirect(DTO\SaveMessage $saveMessageDto): void
    {
        $html = $this->templateService->render('email/compiled/message_direct.html', [
            'text' => $this->markdownConverter->convertToHtml($saveMessageDto->text),
        ]);

        $this->queueClient->pushTask(
            QueueList::MAIL(),
            new DTO\Mail(
                $saveMessageDto->fromName,
                $saveMessageDto->toEmail,
                $saveMessageDto->toName,
                $saveMessageDto->subject,
                $html,
                $saveMessageDto->attachments,
                $saveMessageDto->uuid
            )
        );
    }

    /**
     * @throws QueueException
     */
    private function sendSecure(DTO\SaveMessage $saveMessageDto): void
    {
        try {
            $mailbox = $this->getMailboxFromMessageDto($saveMessageDto);
            $alias = $this->getAliasFromMessageDto($saveMessageDto, $mailbox);
            $message = $this->convertToMessage($saveMessageDto, $alias);
            $this->messageRepository->save($message);

            $attachments = $this->convertToAttachments($saveMessageDto, $message);
            foreach ($attachments as $attachment) {
                $this->attachmentRepository->save($attachment);
            }

            $this->queueClient->pushTask(
                QueueList::NOTIFICATION(),
                new DTO\Notification($message->uuid, $alias->uuid)
            );
        } catch (RepositoryException $exception) {
            throw QueueException::fromThrowable($exception);
        }
    }

    /**
     * @throws QueueException
     * @throws RepositoryException
     */
    private function getMailboxFromMessageDto(Dto\SaveMessage $saveMessageDto): ?Mailbox
    {
        if ($saveMessageDto->pseudoBsnToken === null) {
            $this->logger->debug('pseudoBsnByToken empty, no mailbox created/required');
            return null;
        }

        try {
            $pseudoBsn = $this->bsnService->getByToken($saveMessageDto->pseudoBsnToken);
        } catch (BsnServiceException $bsnServiceException) {
            throw QueueException::fromThrowable($bsnServiceException);
        }
        $this->logger->debug('Retrieved pseudoBsn by token', ['pseudoBsn' => $pseudoBsn->guid]);

        try {
            $mailbox = $this->mailboxRepository->getByPseudoBsn($pseudoBsn->guid);
            $this->logger->debug('mailbox found', ['mailboxUuid' => $mailbox->uuid]);
        } catch (EntityNotFoundException) {
            $mailbox = new Mailbox(UuidService::generate(), $pseudoBsn->guid);
            $this->logger->debug('created new mailbox', ['mailboxUuid' => $mailbox->uuid]);
            $this->mailboxRepository->save($mailbox);
        }

        return $mailbox;
    }

    /**
     * @throws RepositoryException
     */
    private function getAliasFromMessageDto(DTO\SaveMessage $saveMessageDto, ?Mailbox $mailbox): Alias
    {
        $this->logger->debug('getting alias from message', ['messageUuid' => $saveMessageDto->uuid]);

        try {
            $alias = $this->aliasRepository->getByPlatformIdentifier(
                $saveMessageDto->platform,
                $saveMessageDto->platformIdentifier,
            );
            $this->logger->debug('alias found', ['aliasUuid' => $alias->uuid]);

            if ($mailbox?->pseudoBsn !== null && $alias->mailboxUuid === null) {
                $this->logger->debug('alias is not connected to mailbox yet..', ['aliasUuid' => $alias->uuid]);
                $this->logger->debug('Adding mailbox to alias..', ['aliasUuid' => $alias->uuid]);
                $alias->mailboxUuid = $mailbox->uuid;
                $this->aliasRepository->save($alias);

                $this->logger->debug('Updating all messages with MailboxUuid for this alias.', ['aliasUuid' => $alias->uuid]);
                $this->messageRepository->updateMailboxUuidByAliasUuid($mailbox->uuid, $alias->uuid);
            }

            if ($alias->expiresAt !== $saveMessageDto->aliasExpiresAt) {
                $alias->expiresAt = $saveMessageDto->aliasExpiresAt;
                $this->aliasRepository->save($alias);
            }
        } catch (EntityNotFoundException) {
            $alias = new Alias(
                UuidService::generate(),
                $mailbox?->uuid,
                $saveMessageDto->platform,
                $saveMessageDto->platformIdentifier,
                $saveMessageDto->aliasExpiresAt,
                $saveMessageDto->toEmail,
                CarbonImmutable::now(),
            );
            $this->logger->debug('created new alias', ['aliasUuid' => $alias->uuid]);
            $this->aliasRepository->save($alias);
        }

        return $alias;
    }

    private function convertToMessage(DTO\SaveMessage $saveMessageDto, Alias $alias): Message
    {
        $message = new Message(
            $saveMessageDto->uuid,
            $saveMessageDto->platform,
            $alias->uuid,
            $alias->mailboxUuid,
            $saveMessageDto->fromName,
            $saveMessageDto->fromEmail,
            $saveMessageDto->toName,
            $saveMessageDto->toEmail,
            $saveMessageDto->phoneNumber,
            $saveMessageDto->subject,
            $saveMessageDto->text,
            $saveMessageDto->footer,
            $saveMessageDto->attachmentsEncryptionKey,
            $saveMessageDto->expiresAt,
            CarbonImmutable::now(),
            $saveMessageDto->identityRequired,
        );
        $this->logger->debug('converted messageData to message', ['messageUuid' => $message->uuid]);

        return $message;
    }

    /**
     * @return Attachment[]
     */
    private function convertToAttachments(DTO\SaveMessage $saveMessageDto, Message $message): array
    {
        $messageAttachments = $saveMessageDto->attachments;

        $attachments = [];
        foreach ($messageAttachments as $messageAttachment) {
            $uuid = $messageAttachment['uuid'];
            $filename = $messageAttachment['filename'];
            $mimeType = $messageAttachment['mime_type'];

            $attachment = new Attachment(
                $uuid,
                $message->uuid,
                $filename,
                $mimeType,
            );

            $attachments[] = $attachment;
        }

        return $attachments;
    }
}
