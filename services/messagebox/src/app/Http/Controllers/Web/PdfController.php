<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\Enums\Error;
use App\Services\MessageService;
use App\Services\PdfService;
use Illuminate\Routing\Redirector;
use MinVWS\Audit\Models\AuditEvent;
use MinVWS\Audit\Models\AuditObject;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

class PdfController
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly PdfService $pdfService,
        private readonly Redirector $redirector,
    ) {
    }

    public function download(string $uuid, AuditEvent $auditEvent): Response
    {
        $auditEvent->object(AuditObject::create('pdf-download', $uuid));

        try {
            $message = $this->messageService->getByUuidAndSession($uuid);
        } catch (RepositoryException) {
            return $this->redirector->to(sprintf('error/%s', Error::unknown()->value));
        }

        $pdf = $this->pdfService->generatePdfFromTemplate('message', $message);

        return $pdf->download(sprintf('Bericht %s.pdf', $message->subject));
    }
}
