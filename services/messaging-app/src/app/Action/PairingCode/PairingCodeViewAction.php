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

class PairingCodeViewAction extends Action
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

    protected function action(): ResponseInterface
    {
        $this->auditService->setEventExpected(false);

        $pairingCodeUuid = $this->validationService->getValueFromArray($this->requestArguments, 'uuid');

        $this->logger->debug('getting pairing-code by uuid', ['uuid' => $pairingCodeUuid]);

        try {
            $pairingCode = $this->pairingCodeRepository->getByUuid($pairingCodeUuid);

            if ($pairingCode->messageUuid === null) {
                return $this->notFoundResponse();
            }

            $message = $this->messageRepository->getByUuid($pairingCode->messageUuid);
        } catch (RepositoryException) {
            return $this->notFoundResponse();
        }
        $this->logger->debug('pairingCode found', ['pairingCodeUuid' => $pairingCode->uuid]);

        return $this->jsonResponse($this->pairingCodeResource->convert($pairingCode, $message));
    }
}
