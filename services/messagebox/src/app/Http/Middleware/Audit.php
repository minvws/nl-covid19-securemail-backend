<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Models\AuditEvent;

use function app;

class Audit
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * Returns the audit event action code for the given request method.
     *
     * @param string $method
     *
     * @return string
     */
    private function actionCodeForRequestMethod(string $method): string
    {
        switch ($method) {
            case 'GET':
                return AuditEvent::ACTION_READ;
            case 'POST':
                return AuditEvent::ACTION_CREATE;
            case 'PUT':
                return AuditEvent::ACTION_UPDATE;
            case 'DELETE':
                return AuditEvent::ACTION_DELETE;
            default:
                return AuditEvent::ACTION_EXECUTE;
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $actionCode
     *
     * @return mixed
     */
    public function handle(
        Request $request,
        Closure $next,
        string $actionCode = null,
    ) {
        $actionCode = $actionCode ?? $this->actionCodeForRequestMethod($request->getMethod());

        /** @var Route $route */
        $route = $request->route();

        $auditEvent = $this->auditService->startEvent(
            // Todo: Move PHPDocHelper to Audit package
            // AuditEvent::create($route->getActionName(), $actionCode, PHPDocHelper::getTagAuditEventDescriptionByActionName($route->getActionName()))
            AuditEvent::create($route->getActionName(), $actionCode, 'Not available')
        );

        $app = app();
        if ($app instanceof Application) {
            $app->instance(AuditEvent::class, $auditEvent); // allow injection inside the controller method
        }

        return $next($request);
    }
}
