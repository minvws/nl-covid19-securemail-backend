<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Models\AuditObject as AuditObjectModel;

use function preg_match;

class AuditObject
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $objectType
     * @param string $objectId
     *
     * @return mixed
     */
    public function handle(
        Request $request,
        Closure $next,
        string $objectType,
        string $objectId,
    ) {
        /** @var Route $route */
        $route = $request->route();

        if (preg_match("/^\{(.+)\}$/", $objectId, $matches)) {
            $newObjectId = $route->originalParameter($matches[1]);

            if ($newObjectId) {
                $objectId = $newObjectId;
            }
        }

        $auditEvent = $this->auditService->getCurrentEvent();
        $auditEvent->object(AuditObjectModel::create($objectType, $objectId));

        return $next($request);
    }
}
