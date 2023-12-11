<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Action\OtpCode;

use Exception;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Helpers\PHPDocHelper;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\Audit\Models\AuditObject;
use MinVWS\MessagingApp\Action\Action;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Resource\OtpCodeResource;
use MinVWS\MessagingApp\Service\OtpCode\OtpCodeException;
use MinVWS\MessagingApp\Service\OtpCode\OtpCodeService;
use MinVWS\MessagingApp\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class OtpCodeRequestAction extends Action
{
    public function __construct(
        protected AuditService $auditService,
        protected LoggerInterface $logger,
        private readonly MessageRepository $messageRepository,
        private readonly OtpCodeService $otpCodeService,
        private readonly OtpCodeResource $otpCodeResource,
        private readonly ValidationService $validationService,
    ) {
        parent::__construct($auditService, $logger);
    }

    /**
     * @auditEventDescription Otp login
     *
     * @throws Exception
     */
    protected function action(): ResponseInterface
    {
        return $this->auditService->registerHttpEvent(
            AuditEvent::create(
                __METHOD__,
                AuditEvent::ACTION_EXECUTE,
                PHPDocHelper::getTagAuditEventDescriptionByActionName(__METHOD__)
            ),
            fn (AuditEvent $auditEvent) => $this->doOtpCodeRequestAction($auditEvent)
        );
    }

    /**
     * @throws RepositoryException
     */
    private function doOtpCodeRequestAction(AuditEvent $auditEvent): ResponseInterface
    {
        $messageUuid = $this->validationService->getValueFromArray($this->requestArguments, 'messageUuid');
        $type = $this->validationService->getValueFromArray($this->requestArguments, 'type');

        $auditEvent->object(AuditObject::create('message', $messageUuid));

        $this->logger->debug('sending otp-code', [
            'messageUuid' => $messageUuid,
            'type' => $type,
        ]);

        try {
            $otpCode = $this->otpCodeService->createFromMessageUuidAndType($messageUuid, $type);
            $this->otpCodeService->send($otpCode);
        } catch (OtpCodeException $otpCodeException) {
            return $this->jsonResponse([
                'error' => $otpCodeException->getMessage(),
            ], 500);
        }

        $message = $this->messageRepository->getByUuid($messageUuid);

        return $this->jsonResponse($this->otpCodeResource->convert($otpCode, $message->phoneNumber));
    }
}
