<?php

declare(strict_types=1);

namespace App\Http\View\Composers;

use App\Services\AuthenticationService;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function collect;
use function config;
use function floor;

class LayoutComposer
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly Session $session,
    ) {
    }

    public function compose(View $view): void
    {
        $version = config('app.debug') ? config('app.env_version') : null;

        $view->with('frontendConfiguration', collect([
            'version' => $version,
            'environment' => config('app.env_name'),
            'lifetime' => floor(config('auth.authentication_session_lifetime_in_seconds') / 60),
        ])->jsonSerialize());

        $view->with('isLoggedIn', Auth::check());
        $view->with('pairingCodeResponse', $this->session->get('pairingCodeResponse'));
        $view->with('digidResponse', $this->session->get('digidResponse'));

        $pairingCode = $this->authenticationService->getPairingCode();
        if ($pairingCode) {
            $view->with('sessionMessageUuid', $pairingCode->messageUuid);
        }
    }
}
