<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Action\Message;

use Exception;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Helpers\PHPDocHelper;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\Audit\Models\AuditObject;
use MinVWS\MessagingApp\Action\Action;
use MinVWS\MessagingApp\Exception\MessageNotAuthorisedException;
use MinVWS\MessagingApp\Resource\MessageResource;
use MinVWS\MessagingApp\Service\AttachmentService;
use MinVWS\MessagingApp\Service\MessageService;
use MinVWS\MessagingApp\Service\TokenService;
use MinVWS\MessagingApp\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function sprintf;

class MessageViewAction extends Action
{
    public function __construct(
        protected AuditService $auditService,
        protected LoggerInterface $logger,
        private readonly MessageResource $messageResource,
        private readonly ValidationService $validationService,
        private readonly TokenService $tokenService,
        private readonly MessageService $messageService,
        private readonly AttachmentService $attachmentService,
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
        $messageUuid = $this->validationService->getValueFromArray($this->requestArguments, 'messageUuid');
        $pseudoBsn = $this->tokenService->getAttributeFromToken($this->request, 'pseudoBsn', false);
        $otpCodeUuid = $this->tokenService->getAttributeFromToken($this->request, 'otpCodeUuid', false);

        $auditEvent->object(AuditObject::create('message', $messageUuid));

        try {
            if ($pseudoBsn === null && $otpCodeUuid === null) {
                throw new MessageNotAuthorisedException(
                    'Message can only be retrieved when either an OTP or a Digid login is given.',
                    403,
                );
            }

            // first check if message can be viewed based on pseudoBSN
            if ($pseudoBsn !== null) {
                $this->logger->debug(sprintf('MessageViewAction: authorise %s for %s', $pseudoBsn, $messageUuid));
                try {
                    $message = $this->messageService->getMessageForPseudoBsn($messageUuid, $pseudoBsn);
                    $attachments = $this->attachmentService->getAttachmentsByMessageUuid($message->uuid);
                    $this->messageService->markRead($message);
                    return $this->jsonResponse($this->messageResource->convert($message, $attachments));
                } catch (MessageNotAuthorisedException) {
                    //try to retrieve message by otp code.
                    $this->logger->debug(sprintf('MessageViewAction: %s is not authorised for %s', $pseudoBsn, $messageUuid));
                } catch (RepositoryException) {
                    return $this->notFoundResponse();
                }
            }

            // otherwise check otp code for current message
            if ($otpCodeUuid !== null) {
                $message = $this->messageService->getMessageForOtpCode($messageUuid, $otpCodeUuid);
                $attachments = $this->attachmentService->getAttachmentsByMessageUuid($message->uuid);
                $this->messageService->markRead($message);
                $this->logger->debug('resource', [$this->messageResource->convert($message, $attachments)]);
                return $this->jsonResponse($this->messageResource->convert($message, $attachments));
            }

            throw new MessageNotAuthorisedException('Current Digid account is not authorised to retrieve message', 403);
        } catch (RepositoryException) {
            return $this->notFoundResponse();
        } catch (MessageNotAuthorisedException $e) {
            return $this->notAllowedResponse($e);
        }
    }
}
