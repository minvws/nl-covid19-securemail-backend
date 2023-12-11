<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Exceptions\PairingCodeInvalidException;
use App\Models\Enums\Error;
use App\Models\PairingCode;
use App\Services\AuthenticationService;
use App\Services\MessageService;
use App\Services\PairingCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\Audit\Models\AuditObject;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\EncryptionException;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use SecureMail\Shared\Application\Helpers\EncryptionHelper;

use function base64_decode;
use function config;
use function sprintf;
use function urldecode;

class PairingCodeController
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly LoggerInterface $logger,
        private readonly PairingCodeService $pairingCodeService,
        private readonly Redirector $redirector,
        private readonly MessageService $messageService,
    ) {
    }

    /**
     * @auditEventDescription Inloggen via OTP
     * @return RedirectResponse
     */
    public function loginByCode(string $code, AuditEvent $auditEvent): RedirectResponse
    {
        try {
            $pairingCodeUuid = EncryptionHelper::decrypt(
                base64_decode(config('encryption.pairing_code.private_key')),
                base64_decode(config('encryption.pairing_code.public_key')),
                urldecode($code)
            );
        } catch (EncryptionException $encryptionException) {
            $this->logger->debug('encryption exception', ['message' => $encryptionException->getMessage()]);

            return $this->redirector->to(sprintf('error/%s', Error::pairingCodeInvalid()->value));
        }

        try {
            $pairingCode = $this->pairingCodeService->getByUuid($pairingCodeUuid);
            $auditEvent->object(AuditObject::create('message', $pairingCode->messageUuid));
        } catch (PairingCodeInvalidException $pairingCodeInvalidException) {
            $this->logger->debug('pairingCode invalid', ['message' => $pairingCodeInvalidException->getMessage()]);

            return $this->redirector->to(sprintf('error/%s', Error::pairingCodeInvalid()->value));
        }

        if ($pairingCode->validUntil->isPast()) {
            $this->logger->debug('pairingCode expired', ['pairingCodeUuid' => $pairingCode->uuid]);

            return $this->getRedirectResponse(Error::pairingCodeExpired(), $pairingCode);
        }

        // When a user is already logged in we should check if he is allowed to view the message requested by the
        // posted pairingcode.
        if (Auth::check()) {
            $messageUuid = $pairingCode->messageUuid;

            try {
                $this->messageService->getByUuidAndSession($messageUuid);
            } catch (RepositoryException $exception) {
                if ($exception->getCode() === Response::HTTP_NOT_FOUND) {
                    return $this->getRedirectResponse(Error::messageNotFound(), $pairingCode);
                }
                if ($exception->getCode() === Response::HTTP_FORBIDDEN) {
                    $this->authenticationService->logout();
                }
            }
        }

        $this->authenticationService->registerPairingCode($pairingCode);

        return $this->redirector->to('/auth/login');
    }

    public function getRedirectResponse(Error $error, PairingCode $pairingCode): RedirectResponse
    {
        return $this->redirector->to(sprintf('error/%s', $error->value))->with([
            'pairingCodeResponse' => [
                'error' => $error,
                'pairingCodeUuid' => $pairingCode->uuid,
                'emailAddress' => $pairingCode->emailAddress,
            ]
        ]);
    }
}
