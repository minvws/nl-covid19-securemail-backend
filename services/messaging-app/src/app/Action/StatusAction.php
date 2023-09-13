<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Action;

use MinVWS\Audit\AuditService;
use MinVWS\MessagingApp\Repository\MessageRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class StatusAction extends Action
{
    public function __construct(
        protected AuditService $auditService,
        protected LoggerInterface $logger,
        private readonly MessageRepository $messageRepository,
    ) {
        parent::__construct($auditService, $logger);
    }

    public function action(): ResponseInterface
    {
        $this->auditService->setEventExpected(false);

        $messageRepositoryHealth = $this->messageRepository->isHealthy();

        $isHealthy = $messageRepositoryHealth;

        $healthCheckResult = [
            'isHealthy' => $isHealthy,
            'results' => [
                'private-mysql' => [
                    'isHealthy' => $messageRepositoryHealth,
                ],
            ],
        ];

        return $this->jsonResponse($healthCheckResult)
            ->withStatus($isHealthy ? 200 : 503);
    }
}
