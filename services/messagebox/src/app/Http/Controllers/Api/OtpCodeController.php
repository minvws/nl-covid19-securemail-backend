<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostOtpCodeRequest;
use App\Http\Requests\RequestOtpCodeRequest;
use App\Models\OtpUser;
use App\Resources\OtpCodeResource;
use App\Services\AuthenticationService;
use App\Services\MessageService;
use App\Services\OtpCodeException;
use App\Services\OtpCodeService;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;

class OtpCodeController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly LoggerInterface $logger,
        private readonly OtpCodeService $otpCodeService,
        private readonly OtpCodeResource $otpCodeResource,
        private readonly ResponseFactory $response,
        private readonly MessageService $messageService,
    ) {
    }

    public function getInfo(): JsonResponse
    {
        try {
            $pairingCode = $this->authenticationService->getPairingCode();
            if ($pairingCode === null) {
                throw new Exception('no pairing-code found');
            }

            $info = $this->otpCodeService->getInfo($pairingCode);

            return $this->response->json($info);
        } catch (Exception $exception) {
            $this->logger->debug('otp_code info failed', ['message' => $exception->getMessage()]);

            return $this->response->json([
                'error' => $exception->getMessage(),
            ], 404);
        }
    }

    public function postIncorrectPhone(): JsonResponse
    {
        try {
            $pairingCode = $this->authenticationService->getPairingCode();
            if ($pairingCode === null) {
                throw new Exception('no pairing-code found');
            }

            $this->otpCodeService->reportIncorrectPhone($pairingCode);

            return $this->response->json([], 201);
        } catch (Exception $exception) {
            $this->logger->debug('report incorrect phone failed', ['message' => $exception->getMessage()]);

            return $this->response->json([
                'error' => $exception->getMessage(),
            ], 404);
        }
    }

    public function postOtpCode(PostOtpCodeRequest $request): JsonResponse
    {
        try {
            $pairingCode = $this->authenticationService->getPairingCode();
            if ($pairingCode === null) {
                throw new Exception('no pairing-code found');
            }

            $messageUuid = $pairingCode->messageUuid;
            $this->otpCodeService->validate($messageUuid, $request->getPostOtpCode());

            $message = $this->messageService->validateAndRetrieveMessageBySessionAndOtp($messageUuid);
            $this->authenticationService->loginUser(new OtpUser($message->aliasUuid));

            return $this->response->json();
        } catch (Exception $exception) {
            $this->logger->debug('otp_code failed', ['message' => $exception->getMessage()]);

            return $this->response->json([
                'error' => $exception->getMessage(),
            ], 401);
        }
    }

    public function requestOtpCode(RequestOtpCodeRequest $request): JsonResponse
    {
        $pairingCode = $this->authenticationService->getPairingCode();
        if ($pairingCode === null) {
            return $this->response->json(['error' => 'no pairing-code found'], 500);
        }

        try {
            $otpCode = $this->otpCodeService->request(
                $request->getPostLoginType(),
                $pairingCode->messageUuid,
            );

            return $this->response->json([
                'otpCode' => $this->otpCodeResource->convertToResource($otpCode),
            ]);
        } catch (OtpCodeException $otpCodeException) {
            $this->logger->debug('otp_code failed', ['message' => $otpCodeException->getMessage()]);

            return $this->response->json([
                'error' => $otpCodeException->getMessage(),
            ], $otpCodeException->getCode());
        }
    }
}
