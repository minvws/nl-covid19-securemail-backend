<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use MinVWS\Audit\AuditService;

class PageController
{
    public function __construct(
        private readonly ViewFactory $view,
    ) {
    }

    public function page(AuditService $auditService): View
    {
        $auditService->setEventExpected(false);

        return $this->view->make('page');
    }
}
