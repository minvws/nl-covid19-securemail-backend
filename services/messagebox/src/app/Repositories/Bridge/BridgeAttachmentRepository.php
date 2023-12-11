<?php

declare(strict_types=1);

namespace App\Repositories\Bridge;

use App\Models\Attachment;
use App\Repositories\AttachmentRepository;
use App\Repositories\EntityNotFoundException;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class BridgeAttachmentRepository extends BridgeRepository implements AttachmentRepository
{
    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByAttachmentUuidAndMessageUuid(string $attachmentUuid, string $messageUuid): Attachment
    {
        $response = $this->request('attachment-by-uuid', [], [
            'attachmentUuid' => $attachmentUuid,
        ], [
            'messageUuid' => $messageUuid,
        ]);

        return $this->convertToAttachment($response);
    }

    private function convertToAttachment(object $response): Attachment
    {
        return new Attachment(
            $response->uuid,
            $response->name,
            $response->mime_type,
        );
    }
}
