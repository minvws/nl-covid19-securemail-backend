<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Resource;

use Illuminate\Support\Collection;
use MinVWS\MessagingApp\Model\Attachment;
use MinVWS\MessagingApp\Model\Message;

use function base64_encode;

class MessageResource extends AbstractResource
{
    private AttachmentResource $attachmentResource;

    public function __construct(AttachmentResource $attachmentResource)
    {
        $this->attachmentResource = $attachmentResource;
    }

    /**
     * @param Collection<Attachment> $attachments
     */
    public function convert(Message $message, Collection $attachments): array
    {
        return [
            'uuid' => $message->uuid,
            'aliasUuid' => $message->aliasUuid,
            'fromName' => $message->fromName,
            'toName' => $message->toName,
            'subject' => $message->subject,
            'text' => $message->text,
            'footer' => $message->footer,
            'createdAt' => $this->convertDate($message->createdAt),
            'expiresAt' => $this->convertDate($message->expiresAt),
            'attachments' => $this->attachmentResource->convertCollection($attachments),
            'attachmentsEncryptionKey' => $message->attachmentsEncryptionKey !== null ? base64_encode($message->attachmentsEncryptionKey) : null,
        ];
    }

    /**
     * @param Collection<Message> $messages
     */
    public function convertCollection(Collection $messages): array
    {
        $converted = [];
        foreach ($messages as $message) {
            $converted[] = [
                'uuid' => $message->uuid,
                'aliasUuid' => $message->aliasUuid,
                'fromName' => $message->fromName,
                'subject' => $message->subject,
                'createdAt' => $this->convertDate($message->createdAt),
                'isRead' => $message->isRead(),
                'hasAttachments' => ($message->attachmentCount > 0),
            ];
        }

        return $converted;
    }
}
