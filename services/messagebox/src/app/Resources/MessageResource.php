<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Attachment;
use App\Models\Message;
use Illuminate\Config\Repository;
use League\CommonMark\ConverterInterface;

class MessageResource extends Resource
{
    public function __construct(
        private readonly AttachmentResource $attachmentResource,
        private readonly ConverterInterface $markdownConverter,
        private readonly Repository $config,
    ) {
    }

    public function convertToResource(Message $message): array
    {
        $markdownEnabled = $this->config->get('feature.markdownEnabled');

        if ($markdownEnabled) {
            $text = $this->markdownConverter->convert($message->text)->getContent();
            $footer = $this->markdownConverter->convert($message->footer)->getContent();
        } else {
            $text = $message->text;
            $footer = $message->footer;
        }

        return [
            'uuid' => $message->uuid,
            'fromName' => $message->fromName,
            'toName' => $message->toName,
            'subject' => $message->subject,
            'text' => $text,
            'footer' => $footer,
            'createdAt' => $this->formatDate($message->createdAt),
            'expiresAt' => $message->expiresAt !== null ? $this->formatDate($message->expiresAt) : null,
            'attachments' => $message->attachments->map(function (Attachment $attachment): array {
                return $this->attachmentResource->convertToResource($attachment);
            }),
        ];
    }
}
