<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as AuthMiddleware;
use Illuminate\Http\Request;

use function route;

class Authenticate extends AuthMiddleware
{
    /**
     * @param Request $request
     */
    protected function redirectTo($request): ?string
    {
        return route('page');
    }
}
