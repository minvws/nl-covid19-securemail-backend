<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;

use function DI\env;
use function DI\factory;

return [
    'logger.name' => 'console',
    'logger.channel' => getenv('LOG_CHANNEL'),
    'logger.level' => getenv('LOG_LEVEL'),
    'logger.path' => dirname(__DIR__) . '/logs/app.log',

    'redis.connection' => [
        'host' => env('REDIS_HOST'),
        'port' => env('REDIS_PORT'),
        'username' => env('REDIS_USERNAME', null),
        'password' => env('REDIS_PASSWORD'),
        'read_write_timeout' => php_sapi_name() === 'cli' ? -1 : 60
    ],
    'redis.parameters' => factory(function (ContainerInterface $c) {
        $service = getenv('REDIS_SENTINEL_SERVICE');
        if (empty($service)) {
            return $c->get('redis.connection');
        } else {
            return [$c->get('redis.connection')];
        }
    }),
    'redis.options' => factory(function (): array {
        $service = getenv('REDIS_SENTINEL_SERVICE');

        $options = [];
        if (!empty($service)) {
            $options['replication'] = 'sentinel';
            $options['service'] = $service;
        }

        return $options;
    }),
    'messagingApp' => [
        'base_uri' => env('MESSAGING_APP_BASE_URI')
    ]
];
