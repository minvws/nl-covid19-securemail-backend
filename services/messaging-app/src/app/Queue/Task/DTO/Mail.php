<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Queue\Task\DTO;

class Mail implements Dto
{
    public function __construct(
        public readonly string $fromName,
        public readonly string $toEmail,
        public readonly string $toName,
        public readonly string $subject,
        public readonly string $html,
        public readonly ?array $attachments,
        public readonly string $uuid,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'fromName' => $this->fromName,
            'toEmail' => $this->toEmail,
            'toName' => $this->toName,
            'subject' => $this->subject,
            'html' => $this->html,
            'attachments' => $this->attachments,
            'uuid' => $this->uuid
        ];
    }

    public static function jsonDeserialize(array $data): self
    {
        return new self(
            $data['fromName'],
            $data['toEmail'],
            $data['toName'],
            $data['subject'],
            $data['html'],
            $data['attachments'],
            $data['uuid']
        );
    }
}
