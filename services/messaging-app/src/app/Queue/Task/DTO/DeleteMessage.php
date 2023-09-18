<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Queue\Task\DTO;

class DeleteMessage implements Dto
{
    public function __construct(
        public readonly string $uuid,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
        ];
    }

    public static function jsonDeserialize(array $data): self
    {
        return new self(
            $data['uuid']
        );
    }
}
