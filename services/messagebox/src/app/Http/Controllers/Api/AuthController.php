<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use App\Services\OtpCodeService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Models\AuditEvent;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly OtpCodeService $otpCodeService,
        private readonly ResponseFactory $response,
    ) {
    }

    public function getOptions(AuditService $auditService): JsonResponse
    {
        $auditService->setEventExpected(false);

        $pairingCode = $this->authenticationService->getPairingCode();

        if ($pairingCode === null) {
            $loginTypes = [];
            $name = null;
        } else {
            $loginTypes = $this->authenticationService->getLoginTypes($pairingCode);
            $name = $pairingCode->toName;
        }

        return $this->response->json([
            'loginTypes' => $loginTypes,
            'name' => $name,
        ]);
    }

    public function keepAlive(AuditService $auditService): JsonResponse
    {
        $auditService->setEventExpected(false);

        return $this->response->json();
    }

    public function logout(AuditEvent $auditEvent): JsonResponse
    {
        $auditEvent->actionCode(AuditEvent::ACTION_EXECUTE);

        $this->authenticationService->logout();
        $this->otpCodeService->clear();

        return $this->response->json();
    }
}
