<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Queue\Task\DTO;

class Notification implements Dto
{
    public function __construct(
        public readonly string $messageUuid,
        public readonly string $aliasUuid,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'messageUuid' => $this->messageUuid,
            'aliasUuid' => $this->aliasUuid,
        ];
    }

    public static function jsonDeserialize(array $data): self
    {
        return new self(
            $data['messageUuid'],
            $data['aliasUuid']
        );
    }
}
