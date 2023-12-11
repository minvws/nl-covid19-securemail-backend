<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Action\Message;

use Exception;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Helpers\PHPDocHelper;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\Audit\Models\AuditObject;
use MinVWS\MessagingApp\Action\Action;
use MinVWS\MessagingApp\Model\Message;
use MinVWS\MessagingApp\Resource\MessageResource;
use MinVWS\MessagingApp\Service\MessageService;
use MinVWS\MessagingApp\Service\TokenService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class MessageIndexAction extends Action
{
    public function __construct(
        protected AuditService $auditService,
        protected LoggerInterface $logger,
        private readonly MessageResource $messageResource,
        private readonly MessageService $messageService,
        private readonly TokenService $tokenService,
    ) {
        parent::__construct($auditService, $logger);
    }

    /**
     * @auditEventDescription Berichten in mailbox ophalen
     *
     * @throws Exception
     */
    protected function action(): ResponseInterface
    {
        return $this->auditService->registerHttpEvent(
            AuditEvent::create(
                __METHOD__,
                AuditEvent::ACTION_READ,
                PHPDocHelper::getTagAuditEventDescriptionByActionName(__METHOD__),
            ),
            fn(AuditEvent $auditEvent) => $this->doMessageIndexAction($auditEvent),
        );
    }

    private function doMessageIndexAction(AuditEvent $auditEvent): ResponseInterface
    {
        $pseudoBsn = $this->tokenService->getAttributeFromToken($this->request, 'pseudoBsn', false);
        $aliasUuid = $this->tokenService->getAttributeFromToken($this->request, 'aliasUuid', false);

        $auditEvent->object(AuditObject::create('mailbox', $pseudoBsn ?? '')->detail('aliasUuid', $aliasUuid ?? ''));

        $messages = $this->messageService->get($pseudoBsn, $aliasUuid);

        $auditEvent->objects(AuditObject::createArray(
            $messages->all(),
            fn(Message $message) => AuditObject::create('message', $message->uuid)
        ));

        return $this->jsonResponse($this->messageResource->convertCollection($messages));
    }
}
