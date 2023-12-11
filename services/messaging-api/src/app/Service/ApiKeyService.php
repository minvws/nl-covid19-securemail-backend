<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Service;

use Selective\Validation\Exception\ValidationException;

use function array_search;

class ApiKeyService
{
    public function __construct(
        private readonly array $apiKeys,
    ) {
    }

    public function getPlatform(string $apiKey): string
    {
        $platform = array_search($apiKey, $this->apiKeys, true);

        if (!$platform) {
            throw new ValidationException('incorrect or missing api-key');
        }

        return $platform;
    }
}
