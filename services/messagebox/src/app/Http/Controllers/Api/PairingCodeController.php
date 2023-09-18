<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\PairingCodeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PostPairingCodeRenewRequest;
use App\Http\Requests\PostPairingCodeRequest;
use App\Models\Enums\Error;
use App\Services\AuthenticationService;
use App\Services\MessageService;
use App\Services\PairingCodeService;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\Audit\Models\AuditObject;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class PairingCodeController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly LoggerInterface $logger,
        private readonly PairingCodeService $pairingCodeService,
        private readonly ResponseFactory $response,
        private readonly MessageService $messageService,
    ) {
    }

    /**
     * @auditEventDescription Pairing code valideren
     * @return JsonResponse
     */
    public function postPairingCode(PostPairingCodeRequest $request, AuditEvent $auditEvent): JsonResponse
    {
        try {
            $pairingCode = $this->pairingCodeService->getByEmailAddress(
                $request->getPostEmailAddress(),
                $request->getPostPairingCode()
            );
            $auditEvent->object(AuditObject::create('message', $pairingCode->messageUuid));
            if ($pairingCode->validUntil->isPast()) {
                return $this->response->json([
                    'error' => Error::pairingCodeExpired(),
                    'emailAddress' => $request->getPostEmailAddress(),
                    'pairingCodeUuid' => $pairingCode->uuid,
                ], 410);
            }

            // When a user is already logged in we should check if he is allowed to view the message requested by the
            // posted pairingcode.
            if (Auth::check()) {
                $messageUuid = $pairingCode->messageUuid;

                try {
                    $this->messageService->getByUuidAndSession($messageUuid);
                } catch (RepositoryException $exception) {
                    if ($exception->getCode() === Response::HTTP_FORBIDDEN) {
                        $this->authenticationService->logout();
                    }
                }
            }

            $this->authenticationService->registerPairingCode($pairingCode);

            return $this->response->json();
        } catch (Exception $exception) {
            $this->logger->debug('pairing_code failed', ['message' => $exception->getMessage()]);

            return $this->response->json([
                'error' => Error::pairingCodeInvalid(),
                'emailAddress' => $request->getPostEmailAddress(),
                'pairingCodeUuid' => null,
            ], 401);
        }
    }

    /**
     * @auditEventDescription Pairing code vernieuwen
     * @return JsonResponse
     */
    public function postPairingCodeRenew(PostPairingCodeRenewRequest $request, AuditEvent $auditEvent): JsonResponse
    {
        $auditEvent->object(AuditObject::create('paring-code-login', $request->getPostPairingCodeUuid()));
        try {
            $this->pairingCodeService->renew($request->getPostPairingCodeUuid());

            return $this->response->json([], 201);
        } catch (PairingCodeException $pairingCodeException) {
            $this->logger->debug('pairing_code renew failed', ['message' => $pairingCodeException->getMessage()]);

            return $this->response->json([
                'error' => $pairingCodeException->getMessage(),
            ], 500);
        }
    }
}
