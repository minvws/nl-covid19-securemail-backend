<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use function abort_if;
use function config;

/**
 * Aborts request if the given feature is disabled
 */
class Config
{
    /**
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $actionCode)
    {
        abort_if(!config($actionCode, false), 403);

        return $next($request);
    }
}
