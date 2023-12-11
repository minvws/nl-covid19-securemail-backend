<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\MessageRepository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use MinVWS\Audit\AuditService;

class StatusController extends Controller
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function ping(AuditService $auditService): Response
    {
        $auditService->setEventExpected(false);

        return $this->responseFactory->make('PONG');
    }

    public function status(AuditService $auditService): JsonResponse
    {
        $auditService->setEventExpected(false);

        $messageRepositoryHealth = $this->messageRepository->isHealthy();

        $healthStatus = $messageRepositoryHealth;

        $healthCheckResult = [
            'isHealthy' => $healthStatus,
            'results' => [
                'bridge-redis' => [
                    'isHealthy' => $messageRepositoryHealth,
                ]
            ],
        ];

        return $this->responseFactory->json($healthCheckResult, $healthStatus ? 200 : 503);
    }
}
