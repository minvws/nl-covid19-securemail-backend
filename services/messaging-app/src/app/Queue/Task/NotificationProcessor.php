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
use MinVWS\MessagingApp\Enum\QueueList;
use MinVWS\MessagingApp\Model\Message;
use MinVWS\MessagingApp\Queue\QueueClient;
use MinVWS\MessagingApp\Queue\QueueException;
use MinVWS\MessagingApp\Queue\Task\DTO;
use MinVWS\MessagingApp\Repository\EntityNotFoundException;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Service\PairingCodeException;
use MinVWS\MessagingApp\Service\PairingCodeService;
use MinVWS\MessagingApp\Service\TwigTemplateService;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use Selective\Validation\Exception\ValidationException;

class NotificationProcessor implements TaskProcessor
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MarkdownConverterInterface $markdownConverter,
        private readonly MessageRepository $messageRepository,
        private readonly PairingCodeService $pairingCodeService,
        private readonly QueueClient $queueClient,
        private readonly TwigTemplateService $templateService,
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * @auditEventDescription Zet bericht klaar voor email notificatie
     * @throws QueueException
     * @throws Exception
     */
    public function process(DTO\Dto $task): void
    {
        if (!$task instanceof DTO\Notification) {
            throw new QueueException('task is not of type message');
        }

        $this->logger->debug('process message (notification)', ['messageUuid' => $task->messageUuid]);

        $this->auditService->registerEvent(
            AuditEvent::create(__METHOD__, AuditEvent::ACTION_EXECUTE, PHPDocHelper::getTagAuditEventDescriptionByActionName(__METHOD__))
                ->object(AuditObject::create('message', $task->messageUuid)->detail('alias', $task->aliasUuid)),
            function (AuditEvent $auditEvent) use ($task) {
                $this->doProcess($task);
            }
        );

        $this->auditService->finalizeEvent();
    }

    /**
     * @throws QueueException
     */
    protected function doProcess(DTO\Notification $task): void
    {
        try {
            $message = $this->messageRepository->getByUuid($task->messageUuid);
        } catch (EntityNotFoundException) {
            $this->logger->debug('no message found');

            return;
        } catch (RepositoryException $repositoryException) {
            throw QueueException::fromThrowable($repositoryException);
        }

        try {
            $this->queueClient->pushTask(QueueList::MAIL(), $this->createMailDtoFromMessage($message));

            $message->notificationSentAt = CarbonImmutable::now();
            $this->messageRepository->save($message);
        } catch (PairingCodeException | RepositoryException | ValidationException $exception) {
            throw QueueException::fromThrowable($exception);
        }
    }

    /**
     * @throws PairingCodeException
     */
    private function createMailDtoFromMessage(Message $message): DTO\Mail
    {
        $this->logger->debug('creating pairing-code mail');
        $pairingCode = $this->pairingCodeService->generateForMessage($message);

        $html = $this->templateService->render('email/compiled/message_secure.html', [
            'name' => $message->toName,
            'pairing_code' => $pairingCode->code,
            'messagebox_url' => $this->pairingCodeService->generateMessageboxUrl($pairingCode),
            'footer' => $this->markdownConverter->convertToHtml($message->footer),
        ]);

        return $this->createMailDto($message, $html);
    }

    private function createMailDto(Message $message, string $html): DTO\Mail
    {
        return new DTO\Mail(
            $message->fromName,
            $message->toEmail,
            $message->toName,
            'Er staat een bericht voor u klaar in MijnGGDContact',
            $html,
            null,
            $message->uuid,
        );
    }
}
