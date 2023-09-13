<?php

use MinVWS\MessagingApp\Middleware\JwtAuthenticationHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
    'jwt' => [
        'algorithm' => ['HS256'],
        'path' => '/api/v1/',
        'ignore' => [
            '/api/v1/ping',
            '/api/v1/status',
        ],
        'secure' => getenv('JWT_SECURE') !== 'false',
        'secret' => [
            // use "kid"-paremeter in jwt-token header as platform-identifier
            'messagebox' => getenv('MESSAGEBOX_JWT_SECRET'),
        ],
        'before' => function (ServerRequestInterface $request, array $arguments): ServerRequestInterface {
            return JwtAuthenticationHelper::before($request, $arguments, (int) getenv('JWT_MAX_LIFETIME'));
        },
        'error' => function (ResponseInterface $response, array $arguments): int {
            return JwtAuthenticationHelper::error($response, $arguments);
        },
    ],
    'logger' => [
        'channel' => getenv('LOG_CHANNEL'),
        'level' => getenv('LOG_LEVEL'),
        'name' => 'app',
        'path' => sprintf('%s/logs/app.log', dirname(__DIR__)),
    ],
    'redis' => [
        'host' => getenv('REDIS_HOST'),
        'port' => getenv('REDIS_PORT'),
        'username' => getenv('REDIS_USERNAME'),
        'password' => getenv('REDIS_PASSWORD'),
        'read_write_timeout' => php_sapi_name() === 'cli' ? -1 : 60,
        'lists' => [
            'message_delete' => getenv('REDIS_LIST_MESSAGE_DELETE'),
            'message_save' => getenv('REDIS_LIST_MESSAGE_SAVE'),
            'notification' => getenv('REDIS_LIST_NOTIFICATION'),
            'mail' => getenv('REDIS_LIST_MAIL'),
        ],
    ],
    'redis-sentinel' => getenv('REDIS_SENTINEL_SERVICE'),
    'redis-hsm' => [
        'host' => getenv('HSM_REDIS_HOST'),
        'port' => getenv('HSM_REDIS_PORT'),
        'username' => getenv('HSM_REDIS_USERNAME'),
        'password' => getenv('HSM_REDIS_PASSWORD'),
        'read_write_timeout' => php_sapi_name() === 'cli' ? -1 : 60,
    ],
    'redis-hsm-sentinel' => getenv('HSM_REDIS_SENTINEL_SERVICE'),
    'smtp' => [
        'user' => getenv('SMTP_USER'),
        'pass' => getenv('SMTP_PASS'),
        'host' => getenv('SMTP_HOST'),
        'port' => getenv('SMTP_PORT'),
    ],
    'mail' => [
        'default_from_address' => getenv('MAIL_DEFAULT_FROM_ADDRESS'),
    ],
    'messagebox' => [
        'url' => getenv('MESSAGEBOX_URL'),
    ],
    'pairing_code' => [
        'token_allowed_charachters' => 'ABCDEFGHJKMNPQRSTUVWXYX123456789',
        'token_length' => 6,
        'token_lifetime_in_hours' => getenv('PAIRING_CODE_LIFETIME_IN_HOURS'),
        'private_key' => getenv('PAIRING_CODE_MESSAGING_APP_PRIVATE_KEY'),
        'public_key' => getenv('PAIRING_CODE_MESSAGEBOX_PUBLIC_KEY'),
    ],
    'otp' => [
        'test_mode' => getenv('SMS_PROVIDER') === 'local',
        'sms_enabled' => getenv('OTP_SMS_ENABLED'),
    ],
    'security' => [
        'hash_salt' => getenv('HASH_SALT'),
        'module_type' => getenv('SECURITY_MODULE_TYPE'),
        'sim_key_path' => getenv('SECURITY_MODULE_SIM_KEY_PATH'),
        'storage_term' => [
            'short' => [
                'cleanup_interval' => getenv('SECURITY_MODULE_STORAGE_TERM_SHORT_CLEANUP_INTERVAL'),
                'active_interval' => getenv('SECURITY_MODULE_STORAGE_TERM_SHORT_ACTIVE_INTERVAL'),
            ],
        ],
    ],
    'sms' => [
        'sender_name' => getenv('SMS_SENDER_NAME'),
        'provider' => getenv('SMS_PROVIDER'),
        'spryng' => [
            'api_key' => getenv('SMS_SPRYNG_API_KEY'),
            'route' => getenv('SMS_SPRYNG_API_ROUTE_NUMBER') ? getenv('SMS_SPRYNG_API_ROUTE_NUMBER') : 'business',
        ],
    ],
    'pseudo_bsn_service' => getenv('PSEUDO_BSN_SERVICE'),
    'mittens' => [
        'client_options' => [
            'base_uri' => getenv('MITTENS_BASE_URI'),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'cert' => getenv('MITTENS_CLIENT_SSL_CERT'),
            'ssl_key' => getenv('MITTENS_CLIENT_SSL_KEY'),
        ],
        'digid_access_token' => getenv('MITTENS_DIGID_ACCESS_TOKEN'),
    ],
    'queue' => [
        'task_limit_per_run' => getenv('QUEUE_TASK_LIMIT_PER_RUN') ? getenv('QUEUE_TASK_LIMIT_PER_RUN') : 10,
    ],
];
