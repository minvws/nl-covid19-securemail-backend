<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Auth;

use App\Exceptions\MaxAuthenticationCancelledException;
use App\Exceptions\MaxException;
use App\Http\Requests\Auth\MaxDigidLoginRequest;
use App\Models\DigidUser;
use App\Models\Enums\Error;
use App\Services\AuthenticationService;
use App\Services\MaxDigidAuthService;
use App\Services\MessageService;
use Exception;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Models\AuditEvent;
use SecureMail\Shared\Application\Exceptions\BsnServiceException;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use Symfony\Component\HttpFoundation\RedirectResponse;

use function array_merge;
use function sprintf;

class MaxDigidController
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly MaxDigidAuthService $maxDigidAuthService,
        private readonly Redirector $redirector,
        private readonly MessageService $messageService,
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * @auditEventDescription Bezoekr omleiden ivm digid login
     */
    public function redirectToProvider(AuditEvent $auditEvent): RedirectResponse
    {
        $auditEvent->actionCode(AuditEvent::ACTION_EXECUTE);
        try {
            return new RedirectResponse($this->maxDigidAuthService->getAuthorizeUrl());
        } catch (Exception $e) {
            Log::critical("Unable to generate redirect url for login", [$e]);

            return $this->logoutWithError(Error::digidServiceUnavailable());
        }
    }

    /**
     * @auditEventDescription Succesvol ingelogd via digid
     *
     * @throws Exception
     */
    public function handleProviderCallback(MaxDigidLoginRequest $request, AuditEvent $auditEvent): RedirectResponse
    {
        $auditEvent->actionCode(AuditEvent::ACTION_EXECUTE);
        try {
            $digidToken = $this->maxDigidAuthService->requestNewAccessToken($request->getCode(), $request->getState());
        } catch (MaxAuthenticationCancelledException $exception) {
            Log::info($exception->getMessage());

            return $this->logoutWithError(Error::digidCanceled());
        } catch (MaxException $exception) {
            Log::critical('Max Digid request failed', [$exception]);

            return $this->logoutWithError(Error::digidAuthError());
        }

        try {
            $user = $this->maxDigidAuthService->getAuthenticatedUser($digidToken);
            $pairingCode = $this->authenticationService->getPairingCode();

            if (!$this->isUserAllowedForMessageInSession($user) && $pairingCode) {
                return $this->logoutWithError(Error::messageUserNotAuthorized(), ['name' => $pairingCode->toName]);
            }
            $this->authenticationService->loginUser($user);
        } catch (RepositoryException $repositoryException) {
            Log::critical('Unable to get authenticated user from database', [$repositoryException]);

            return $this->logoutWithError(Error::digidAuthError());
        } catch (BsnServiceException $e) {
            Log::critical($e->getMessage(), [$e]);

            return $this->logoutWithError(Error::digidAuthError());
        }

        return $this->redirector->route('page');
    }

    private function logoutWithError(Error $error, array $data = []): RedirectResponse
    {
        $this->auditService->getCurrentEvent()->result(
            $error === Error::messageUserNotAuthorized() ? AuditEvent::RESULT_FORBIDDEN : AuditEvent::RESULT_ERROR
        );

        Auth::logout();

        $route = '/';
        if ($this->authenticationService->getPairingCode()) {
            $route = '/auth/login';
        }

        if ($error === Error::messageUserNotAuthorized()) {
            $route = sprintf('error/%s', $error->value);
        }

        $responseData = [
            'status' => 'error',
            'error' => $error,
        ];

        $responseData = array_merge($responseData, $data);

        return $this->redirector->to($route)->with(['digidResponse' => $responseData]);
    }

    /**
     * @throws RepositoryException
     */
    private function isUserAllowedForMessageInSession(DigidUser $user): bool
    {
        $pairingCode = $this->authenticationService->getPairingCode();
        if ($pairingCode === null) {
            //No message in Session
            return true;
        }

        return $this->messageService->isPseudoBsnAllowedForMessage(
            $pairingCode->messageUuid,
            $user->pseudoBsn,
        );
    }
}
