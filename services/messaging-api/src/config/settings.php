<?php

declare(strict_types=1);

use MinVWS\MessagingApi\Middleware\JwtAuthenticationHelper;
use MinVWS\MessagingApi\Middleware\JwtSecretsHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @var array $jwtSecrets
 *
 * Excpected input is a string of key/value pairs for each platform, e.g.:
 * platform1:secret1,platform2:secret2
 * Note: both the platform-identifier AND the secrets should be unique
 *
 * In a request to this api, you can use the "kid"-paremeter in jwt-token header as platform-identifier (e.g. platform1)
 * and sign the token with the corresponding secret (e.g. secret1)
 */
$jwtSecrets = JwtSecretsHelper::getSecretsFromString((string) getenv('MESSAGING_API_JWT_SECRETS'), ':', ',');
$allowedJwtAlgorithms = ['HS256'];

return [
    'attachments' => [
        'filesystem' => (!empty(getenv('ATTACHMENTS_FILESYSTEM')) ? getenv('ATTACHMENTS_FILESYSTEM') : 'attachments-local'),
        'local' => [
            'path' => '/tmp/attachment-data',
        ],
        's3' => [
            'endpoint' => getenv('S3_ATTACHMENTS_ENDPOINT'),
            'access_key' => getenv('S3_ATTACHMENTS_ACCESS_KEY'),
            'secret_key' => getenv('S3_ATTACHMENTS_SECRET_KEY'),
            'region' => getenv('S3_ATTACHMENTS_REGION'),
            'version' => getenv('S3_ATTACHMENTS_VERSION'),
            'bucket' => getenv('S3_ATTACHMENTS_BUCKET'),
        ],
    ],
    'db' => [
        'driver' => 'mysql',
        'host' => getenv('MYSQL_HOST'),
        'database' => getenv('MYSQL_DATABASE'),
        'username' => getenv('MYSQL_USERNAME'),
        'password' => getenv('MYSQL_PASSWORD'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'options' => [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
        ],
    ],
    'error' => [
        'display_error_details' => true,
        'log_errors' => true,
        'log_error_details' => true,
    ],
    'jwt_authentication' => [
        'algorithm' => $allowedJwtAlgorithms,
        'path' => '/api/v1/',
        'ignore' => [
            '/api/v1/ping',
            '/api/v1/status',
        ],
        'secure' => getenv('JWT_SECURE') !== 'false',
        'secret' => $jwtSecrets,
        'before' => function (
            ServerRequestInterface $request,
            array $arguments,
        ) use (
            $allowedJwtAlgorithms,
            $jwtSecrets
        ): ServerRequestInterface {
            return JwtAuthenticationHelper::before(
                $request,
                $arguments,
                $jwtSecrets,
                $allowedJwtAlgorithms,
                (int) getenv('JWT_MAX_LIFETIME')
            );
        },
        'error' => function (ResponseInterface $response, array $arguments): int {
            return JwtAuthenticationHelper::error($response, $arguments);
        },
    ],
    'logger' => [
        'channel' => getenv('LOG_CHANNEL'),
        'level' => getenv('LOG_LEVEL'),
        'name' => 'app',
        'path' => dirname(__DIR__) . '/logs/app.log',
    ],
    'redis' => [
        'scheme' => 'tcp',
        'host' => getenv('REDIS_HOST'),
        'port' => getenv('REDIS_PORT'),
        'username' => getenv('REDIS_USERNAME'),
        'password' => getenv('REDIS_PASSWORD'),
        'lists' => [
            'message_delete' => getenv('REDIS_LIST_MESSAGE_DELETE'),
            'message_save' => getenv('REDIS_LIST_MESSAGE_SAVE'),
        ],
    ],
];
