<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Action;

use MinVWS\Audit\AuditService;
use MinVWS\MessagingApi\Repository\MessageReadRepository;
use MinVWS\MessagingApi\Repository\MessageWriteRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class StatusGetAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        AuditService $auditService,
        private readonly MessageReadRepository $messageReadRepository,
        private readonly MessageWriteRepository $messageWriteRepository,
    ) {
        parent::__construct($logger, $auditService);
    }

    protected function action(): ResponseInterface
    {
        $this->auditService->setEventExpected(false);

        $messageReadRepositoryHealth = $this->messageReadRepository->isHealthy();
        $messageWriteRepositoryHealth = $this->messageWriteRepository->isHealthy();

        $isHealthy = $messageReadRepositoryHealth && $messageWriteRepositoryHealth;
        $healthCheckResult = [
            'isHealthy' => $isHealthy,
            'results' => [
                'private-mysql' => [
                    'isHealthy' => $messageReadRepositoryHealth,
                ],
                'private-redis' => [
                    'isHealthy' => $messageWriteRepositoryHealth,
                ],
            ],
        ];

        return $this->jsonResponse($healthCheckResult)
            ->withStatus($isHealthy ? 200 : 503);
    }
}
