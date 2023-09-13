<?php

declare(strict_types=1);

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'migrations',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => getenv('MYSQL_HOST'),
            'name' => getenv('MYSQL_DATABASE'),
            'user' => getenv('MYSQL_USERNAME'),
            'pass' => getenv('MYSQL_PASSWORD'),
            'port' => '3306',
            'charset' => 'utf8mb4',
        ],
    ],
    'version_order' => 'creation',
];
