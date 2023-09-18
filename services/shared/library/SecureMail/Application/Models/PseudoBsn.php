<?php

declare(strict_types=1);

namespace SecureMail\Shared\Application\Models;

class PseudoBsn
{
    public function __construct(
        public readonly string $guid,
        public readonly string $censoredBsn,
        public readonly string $letters,
        public readonly ?string $token
    ) {
    }

    public function toArray(): array
    {
        return [
            'guid' => $this->guid,
            'censoredBsn' => $this->censoredBsn,
            'letters' => $this->letters,
        ];
    }
}
