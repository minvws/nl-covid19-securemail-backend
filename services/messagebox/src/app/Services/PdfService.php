<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Message;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;

use function sprintf;

class PdfService
{
    public function __construct(
        private readonly DomPdfWrapper $domPdfWrapper,
    ) {
    }

    public function generatePdfFromTemplate(string $template_name, Message $message): DomPdfWrapper
    {
        return $this->domPdfWrapper->loadview(sprintf('pdf.%s', $template_name), ['message' => $message]);
    }
}
