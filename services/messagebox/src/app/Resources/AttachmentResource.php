<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Attachment;

class AttachmentResource extends Resource
{
    public function convertToResource(Attachment $attachment): array
    {
        return [
            'uuid' => $attachment->uuid,
            'name' => $attachment->name,
        ];
    }
}
