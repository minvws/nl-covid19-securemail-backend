<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Resource;

use Illuminate\Support\Collection;
use MinVWS\MessagingApp\Model\Attachment;

class AttachmentResource
{
    public function convert(Attachment $attachment): array
    {
        return [
            'uuid' => $attachment->uuid,
            'name' => $attachment->filename,
            'mime_type' => $attachment->mimeType,
        ];
    }

    /**
     * @param Collection<Attachment> $attachments
     */
    public function convertCollection(Collection $attachments): array
    {
        $converted = [];
        foreach ($attachments as $attachment) {
            $converted[] = [
                'uuid' => $attachment->uuid,
                'name' => $attachment->filename,
                'mime_type' => $attachment->mimeType,
            ];
        }

        return $converted;
    }
}
