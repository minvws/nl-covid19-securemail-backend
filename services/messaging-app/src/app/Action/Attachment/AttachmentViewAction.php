<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Action\Attachment;

use Exception;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Helpers\PHPDocHelper;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\Audit\Models\AuditObject;
use MinVWS\MessagingApp\Action\Action;
use MinVWS\MessagingApp\Resource\AttachmentResource;
use MinVWS\MessagingApp\Service\AttachmentService;
use MinVWS\MessagingApp\Service\TokenService;
use MinVWS\MessagingApp\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class AttachmentViewAction extends Action
{
    public function __construct(
        protected AuditService $auditService,
        protected LoggerInterface $logger,
        private readonly AttachmentResource $attachmentResource,
        private readonly AttachmentService $attachmentService,
        private readonly TokenService $tokenService,
        private readonly ValidationService $validationService,
    ) {
        parent::__construct($auditService, $logger);
    }

    /**
     * @auditEventDescription Lees bericht
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
            fn (AuditEvent $auditEvent) => $this->doMessageViewAction($auditEvent),
        );
    }

    private function doMessageViewAction(AuditEvent $auditEvent): ResponseInterface
    {
        $attachmentUuid = $this->validationService->getValueFromArray($this->requestArguments, 'attachmentUuid');
        $messageUuid = $this->tokenService->getAttributeFromToken($this->request, 'messageUuid');

        $auditEvent->object(AuditObject::create('attachment', $attachmentUuid));

        try {
            $attachment = $this->attachmentService->getByUuidAndMessageUuid($attachmentUuid, $messageUuid);
            return $this->jsonResponse($this->attachmentResource->convert($attachment));
        } catch (RepositoryException) {
            return $this->notFoundResponse();
        }
    }
}
