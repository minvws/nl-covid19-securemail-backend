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
use MinVWS\MessagingApp\Repository\OtpCodeRepository;
use MinVWS\MessagingApp\Resource\OtpCodeResource;
use MinVWS\MessagingApp\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class OtpCodeOptionsAction extends Action
{
    public function __construct(
        protected AuditService $auditService,
        protected LoggerInterface $logger,
        private readonly MessageRepository $messageRepository,
        private readonly OtpCodeRepository $otpCodeRepository,
        private readonly OtpCodeResource $otpCodeResource,
        private readonly ValidationService $validationService,
    ) {
        parent::__construct($auditService, $logger);
    }

    /**
     * @auditEventDescription Otp eigenschappen ophalen
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
            fn (AuditEvent $auditEvent) => $this->doOtpCodeOptionsAction($auditEvent),
        );
    }

    private function doOtpCodeOptionsAction(AuditEvent $auditEvent): ResponseInterface
    {
        $messageUuid = $this->validationService->getValueFromArray($this->getRequestBody(), 'messageUuid');
        $code = $this->validationService->getValueFromArray($this->getRequestBody(), 'otpCode');

        $auditEvent->object(AuditObject::create('message', $messageUuid));

        $this->logger->debug('getting otp-code', [
            'messageUuid' => $messageUuid,
            'otpCode' => $code,
        ]);

        try {
            $otpCode = $this->otpCodeRepository->getByMessageUuidAndCode($messageUuid, $code);
            $message = $this->messageRepository->getByUuid($messageUuid);
        } catch (RepositoryException $repositoryException) {
            $this->logger->debug('otpCode or message not found', [
                'exception message' => $repositoryException->getMessage(),
            ]);

            return $this->jsonResponse(['error' => 'otpCode or message not found'], 404);
        }

        return $this->jsonResponse($this->otpCodeResource->convert($otpCode, $message->phoneNumber));
    }
}
