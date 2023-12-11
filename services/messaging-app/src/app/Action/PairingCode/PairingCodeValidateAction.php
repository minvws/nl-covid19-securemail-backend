<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Action\PairingCode;

use MinVWS\Audit\AuditService;
use MinVWS\MessagingApp\Action\Action;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Repository\PairingCodeRepository;
use MinVWS\MessagingApp\Resource\PairingCodeResource;
use MinVWS\MessagingApp\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class PairingCodeValidateAction extends Action
{
    public function __construct(
        protected AuditService $auditService,
        protected LoggerInterface $logger,
        private readonly MessageRepository $messageRepository,
        private readonly PairingCodeRepository $pairingCodeRepository,
        private readonly PairingCodeResource $pairingCodeResource,
        private readonly ValidationService $validationService,
    ) {
        parent::__construct($auditService, $logger);
    }

    /**
     * @throws RepositoryException
     */
    protected function action(): ResponseInterface
    {
        $this->auditService->setEventExpected(false);

        $emailAddress = $this->validationService->getValueFromArray($this->getRequestBody(), 'emailAddress');
        $code = $this->validationService->getValueFromArray($this->getRequestBody(), 'pairingCode');

        $this->logger->debug('getting pairingCode by emailAddress & code', [
            'emailAddress' => $emailAddress,
            'code' => $code,
        ]);

        try {
            $pairingCode = $this->pairingCodeRepository->getByEmailAddressAndCode($emailAddress, $code);
        } catch (RepositoryException) {
            return $this->notFoundResponse();
        }
        $this->logger->debug('pairingCode found', ['pairingCodeUuid' => $pairingCode->uuid]);

        if ($pairingCode->messageUuid === null) {
            return $this->notFoundResponse();
        }

        $message = $this->messageRepository->getByUuid($pairingCode->messageUuid);

        return $this->jsonResponse($this->pairingCodeResource->convert($pairingCode, $message));
    }
}
