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
use MinVWS\MessagingApp\Repository\EntityNotFoundException;
use MinVWS\MessagingApp\Service\MessageService;
use MinVWS\MessagingApp\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class MessageMarkIncorrectPhoneAction extends Action
{
    public function __construct(
        protected AuditService $auditService,
        protected LoggerInterface $logger,
        private readonly MessageService $messageService,
        private readonly ValidationService $validationService,
    ) {
        parent::__construct($auditService, $logger);
    }

    /**
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
            fn(AuditEvent $auditEvent) => $this->doMessageMarkIncorrectPhoneAction($auditEvent),
        );
    }

    /**
     * @throws MessageException
     */
    private function doMessageMarkIncorrectPhoneAction(AuditEvent $auditEvent): ResponseInterface
    {
        $messageUuid = $this->validationService->getValueFromArray($this->getRequestBody(), 'messageUuid');
        $auditEvent->object(AuditObject::create('message', $messageUuid));

        try {
            $this->messageService->markOtpIncorrectPhoneByMessageUuid($messageUuid);
        } catch (EntityNotFoundException) {
            return $this->notFoundResponse();
        } catch (RepositoryException $repositoryException) {
            throw MessageException::fromThrowable($repositoryException);
        }

        return $this->jsonResponse();
    }
}
