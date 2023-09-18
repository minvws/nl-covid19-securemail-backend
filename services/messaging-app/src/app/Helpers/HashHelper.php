<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Helpers;

use function hash;
use function sprintf;

class HashHelper
{
    public function __construct(
        private readonly string $salt,
    ) {
    }

    public function hash(string $data): string
    {
        return hash('sha256', sprintf('%s#%s', $this->salt, $data));
    }
}
