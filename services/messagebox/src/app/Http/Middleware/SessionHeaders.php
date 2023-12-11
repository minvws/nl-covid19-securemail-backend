<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

use function config;

class SessionHeaders
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        /** @var Response $response */
        $response = $next($request);

        $sessionLifetime = config('auth.authentication_session_lifetime_in_seconds');
        $sessionExpiryDate = CarbonImmutable::now()->addSeconds($sessionLifetime);

        $response->headers->set('X-Session-Expiry-Date', $sessionExpiryDate->format('c'));

        return $response;
    }
}
