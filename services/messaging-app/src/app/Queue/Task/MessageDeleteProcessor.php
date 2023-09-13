<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Queue\Task;

use Exception;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Helpers\PHPDocHelper;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\Audit\Models\AuditObject;
use MinVWS\MessagingApp\Queue\QueueException;
use MinVWS\MessagingApp\Repository\MessageRepository;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class MessageDeleteProcessor implements TaskProcessor
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MessageRepository $messageRepository,
        private readonly AuditService $auditService,
    ) {
    }

    /**
     *
     * @auditEventDescription Verwijder bericht
     *
     * @throws QueueException
     * @throws Exception
     */
    public function process(DTO\Dto $task): void
    {
        if (!$task instanceof DTO\DeleteMessage) {
            throw new QueueException('task is not of type message_delete');
        }

        $this->logger->debug('process message (delete)', ['messageUuid' => $task->uuid]);

        $this->auditService->registerEvent(
            AuditEvent::create(__METHOD__, AuditEvent::ACTION_DELETE, PHPDocHelper::getTagAuditEventDescriptionByActionName(__METHOD__))
                ->object(AuditObject::create('message', $task->uuid)),
            function (AuditEvent $auditEvent) use ($task) {
                try {
                    $this->messageRepository->delete($task->uuid);
                } catch (RepositoryException $repositoryException) {
                    QueueException::fromThrowable($repositoryException);
                }
            }
        );

        $this->auditService->finalizeEvent();
    }
}
