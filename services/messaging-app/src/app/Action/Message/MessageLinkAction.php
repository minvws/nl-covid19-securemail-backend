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
use MinVWS\MessagingApp\Repository\AliasRepository;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Repository\PairingCodeRepository;
use MinVWS\MessagingApp\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class MessageLinkAction extends Action
{
    public function __construct(
        protected AuditService $auditService,
        protected LoggerInterface $logger,
        private readonly AliasRepository $aliasRepository,
        private readonly MessageRepository $messageRepository,
        private readonly PairingCodeRepository $pairingCodeRepository,
        private readonly ValidationService $validationService,
    ) {
        parent::__construct($auditService, $logger);
    }

    /**
     * @auditEventDescription Link bericht aan mailbox
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
            fn (AuditEvent $auditEvent) => $this->doMessageLinkAction($auditEvent),
        );
    }

    /**
     * @throws MessageException
     */
    private function doMessageLinkAction(AuditEvent $auditEvent): ResponseInterface
    {
        $mailboxUuid = $this->validationService->getValueFromArray($this->getRequestBody(), 'mailboxUuid');
        $messageUuid = $this->validationService->getValueFromArray($this->getRequestBody(), 'messageUuid');

        try {
            $auditEvent->object(AuditObject::create('message', $messageUuid));
            $auditEvent->object(AuditObject::create('mailbox', $mailboxUuid));

            $message = $this->messageRepository->getByUuid($messageUuid);

            if ($message->aliasUuid !== null) {
                $alias = $this->aliasRepository->getByUuid($message->aliasUuid);
                $alias->mailboxUuid = $mailboxUuid;
                $this->aliasRepository->save($alias);

                $this->pairingCodeRepository->deleteByMailboxUuid($mailboxUuid);
                $this->messageRepository->updateMailboxUuidByAliasUuid($mailboxUuid, $alias->uuid);
            }
        } catch (RepositoryException $repositoryException) {
            throw MessageException::fromThrowable($repositoryException);
        }

        return $this->createdResponse();
    }
}
