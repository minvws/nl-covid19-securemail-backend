<?php

declare(strict_types=1);

namespace App\Providers;

use App\Helpers\AuditUserHelper;
use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Models\AuditUser;

use function config;

class MiscServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditService::class);
        $this->app->extend(AuditService::class, function (AuditService $auditService, Container $app) {
            $auditService->setService(config('app.name'));
            $auditService->setUserCallback(function () {
                if (config('app.name') === 'messagebox') {
                    return AuditUserHelper::getAuditUser();
                }

                return AuditUser::create(config('app.name'), 'system');
            });

            return $auditService;
        });
    }
}
