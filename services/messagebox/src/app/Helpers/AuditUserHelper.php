<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MinVWS\Audit\Models\AuditUser;

use function app;
use function config;
use function request;

class AuditUserHelper
{
    public static function getAuditUser(): AuditUser
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user) {
                return AuditUser::create(config('app.name'), $user->getAuthIdentifier())
                    ->detail('type', $user->getAuthIdentifierName())
                    ->ip(request() instanceof Request ? request()->ip() : '');
            }

            return AuditUser::create((string)config('app.name'), 'unknown') //sh
                ->ip(request() instanceof Request ? request()->ip() : '');
        }

        if (app()->runningInConsole()) {
            return AuditUser::create(config('app.name'), 'console');
        }

        return AuditUser::create(config('app.name'), 'unknown');
    }
}
