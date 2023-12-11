<?php

declare(strict_types=1);

$publicRedisSentinelService = env('PUBLIC_REDIS_SENTINEL_SERVICE');
if ($publicRedisSentinelService === null) {
    $redisDefault = [
        'url' => env('PUBLIC_REDIS_URL'),
        'host' => env('PUBLIC_REDIS_HOST', '127.0.0.1'),
        'username' => env('PUBLIC_REDIS_USERNAME', null),
        'password' => env('PUBLIC_REDIS_PASSWORD'),
        'port' => (int) env('PUBLIC_REDIS_PORT', 6379),
        'database' => (int) env('PUBLIC_REDIS_DB_DEFAULT', 0),
    ];
    $redisCache = [
        'url' => env('PUBLIC_REDIS_URL'),
        'host' => env('PUBLIC_REDIS_HOST', '127.0.0.1'),
        'username' => env('PUBLIC_REDIS_USERNAME', null),
        'password' => env('PUBLIC_REDIS_PASSWORD'),
        'port' => (int) env('PUBLIC_REDIS_PORT', 6379),
        'database' => (int) env('PUBLIC_REDIS_DB_CACHE', 1),
    ];
} else {
    $publicRedisIpAddresses = gethostbynamel(env('PUBLIC_REDIS_HOST'));
    $publicRedisSentinels = [];
    foreach ($publicRedisIpAddresses as $publicRedisIpAddress) {
        $publicRedisSentinels[] = sprintf('tcp://%s:%s', $publicRedisIpAddress, (int) env('PUBLIC_REDIS_PORT', 26379));
    }

    $redisDefault = [
        ...$publicRedisSentinels,
        'options' => [
            'replication' => 'sentinel',
            'service' => $publicRedisSentinelService,
            'parameters' => [
                'username' => env('PUBLIC_REDIS_USERNAME'),
                'password' => env('PUBLIC_REDIS_PASSWORD'),
                'database' => (int) env('PUBLIC_REDIS_DB_DEFAULT', 0),
            ],
        ],
    ];
    $redisCache = [
        ...$publicRedisSentinels,
        'options' => [
            'replication' => 'sentinel',
            'service' => $publicRedisSentinelService,
            'parameters' => [
                'username' => env('PUBLIC_REDIS_USERNAME'),
                'password' => env('PUBLIC_REDIS_PASSWORD'),
                'database' => (int) env('PUBLIC_REDIS_DB_CACHE', 1),
            ],
        ],
    ];
}

$bridgeRedisSentinelService = env('BRIDGE_REDIS_SENTINEL_SERVICE');
if ($bridgeRedisSentinelService === null) {
    $redisBridge = [
        'url' => env('BRIDGE_REDIS_URL'),
        'host' => env('BRIDGE_REDIS_HOST', '127.0.0.1'),
        'username' => env('BRIDGE_REDIS_USERNAME'),
        'password' => env('BRIDGE_REDIS_PASSWORD'),
        'port' => (int) env('BRIDGE_REDIS_PORT', 6379),
        'database' => (int) env('BRIDGE_REDIS_DB', 0),
    ];
} else {
    $bridgeRedisIpAddresses = gethostbynamel(env('BRIDGE_REDIS_HOST'));
    $bridgeRedisSentinels = [];
    foreach ($bridgeRedisIpAddresses as $bridgeRedisIpAddress) {
        $bridgeRedisSentinels[] = sprintf('tcp://%s:%s', $bridgeRedisIpAddress, (int) env('BRIDGE_REDIS_PORT', 26379));
    }

    $redisBridge = [
        ...$bridgeRedisSentinels,
        'options' => [
            'replication' => 'sentinel',
            'service' => $bridgeRedisSentinelService,
            'parameters' => [
                'username' => env('BRIDGE_REDIS_USERNAME'),
                'password' => env('BRIDGE_REDIS_PASSWORD'),
                'database' => (int) env('BRIDGE_REDIS_DB', 0),
            ],
        ],
    ];
}

return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],
        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],
    ],
    'migrations' => 'migrations',
    'redis' => [
        'client' => env('REDIS_CLIENT', 'predis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', ''),
        ],
        'default' => $redisDefault,
        'cache' => $redisCache,
        'bridge' => $redisBridge,
    ],
];
