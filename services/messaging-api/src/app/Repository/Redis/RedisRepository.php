<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Repository\Redis;

use Laminas\Config\Config;
use Predis\ClientInterface;
use Psr\Log\LoggerInterface;

abstract class RedisRepository
{
    public function __construct(
        protected ClientInterface $client,
        protected Config $config,
        protected LoggerInterface $logger,
    ) {
    }

    public function isHealthy(): bool
    {
        return (string) $this->client->ping() === 'PONG';
    }
}
