<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Action\Message;

use Exception;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Helpers\PHPDocHelper;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\Audit\Models\AuditObject;
use MinVWS\MessagingApp\Action\Action;
use MinVWS\MessagingApp\Exception\MessageException;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class MessageUnlinkAction extends Action
{
    public function __construct(
        protected AuditService $auditService,
        protected LoggerInterface $logger,
        private readonly MessageRepository $messageRepository,
        private readonly ValidationService $validationService,
    ) {
        parent::__construct($auditService, $logger);
    }

    /**
     * @auditEventDescription Ontkoppel bericht van mailbox
     *
     * @throws Exception
     */
    protected function action(): ResponseInterface
    {
        return $this->auditService->registerHttpEvent(
            AuditEvent::create(
                __METHOD__,
                AuditEvent::ACTION_UPDATE,
                PHPDocHelper::getTagAuditEventDescriptionByActionName(__METHOD__),
            ),
            fn (AuditEvent $auditEvent) => $this->doMessageUnlinkAction($auditEvent),
        );
    }

    /**
     * @throws MessageException
     */
    private function doMessageUnlinkAction(AuditEvent $auditEvent): ResponseInterface
    {
        $messageUuid = $this->validationService->getValueFromArray($this->getRequestBody(), 'messageUuid');
        $reason = $this->validationService->getValueFromArray($this->getRequestBody(), 'reason');

        try {
            $auditEvent->object(AuditObject::create('message', $messageUuid));

            $message = $this->messageRepository->getByUuid($messageUuid);
            if ($message->mailboxUuid !== null) {
                $auditEvent->object(AuditObject::create('mailbox', $message->mailboxUuid));
            }

            if ($message->aliasUuid !== null) {
                $this->messageRepository->updateMailboxUuidByAliasUuid(null, $message->aliasUuid);
            }

            $this->logger->debug('unlinked messageUuid from mailbox', [
                'mailboxUuid' => $message->mailboxUuid,
                'messageUuid' => $messageUuid,
                'reason' => $reason,
            ]);
        } catch (RepositoryException $repositoryException) {
            throw MessageException::fromThrowable($repositoryException);
        }

        return $this->jsonResponse();
    }
}
