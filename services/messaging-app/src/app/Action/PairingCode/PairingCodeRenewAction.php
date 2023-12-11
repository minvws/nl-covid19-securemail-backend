<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Action\PairingCode;

use MinVWS\Audit\AuditService;
use MinVWS\MessagingApp\Action\Action;
use MinVWS\MessagingApp\Service\PairingCodeException;
use MinVWS\MessagingApp\Service\PairingCodeService;
use MinVWS\MessagingApp\Service\ValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class PairingCodeRenewAction extends Action
{
    public function __construct(
        protected AuditService $auditService,
        protected LoggerInterface $logger,
        private readonly PairingCodeService $pairingCodeService,
        private readonly ValidationService $validationService,
    ) {
        parent::__construct($auditService, $logger);
    }

    protected function action(): ResponseInterface
    {
        $this->auditService->setEventExpected(false);

        $pairingCodeUuid = $this->validationService->getValueFromArray($this->getRequestBody(), 'pairingCodeUuid');

        $this->logger->debug('renewing pairingCode', ['pairingCodeUuid' => $pairingCodeUuid]);

        try {
            $this->pairingCodeService->renew($pairingCodeUuid);
        } catch (PairingCodeException) {
            return $this->notFoundResponse();
        }
        $this->logger->debug('pairingCode renewed', ['pairingCodeUuid' => $pairingCodeUuid]);

        return $this->createdResponse();
    }
}
