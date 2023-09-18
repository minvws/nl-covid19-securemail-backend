<?php

declare(strict_types=1);

use Aws\S3\S3Client;
use DBCO\Shared\Application\Helpers\DateTimeHelper;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Connectors\ConnectionFactory;
use Laminas\Config\Config;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use MinVWS\Audit\AuditService;
use MinVWS\Audit\Middleware\AuditRequests;
use MinVWS\Audit\Repositories\AuditRepository;
use MinVWS\DBCO\Encryption\Security\HSMSecurityModule;
use MinVWS\DBCO\Encryption\Security\ProxySecurityCache;
use MinVWS\DBCO\Encryption\Security\RedisSecurityCache;
use MinVWS\DBCO\Encryption\Security\SecurityCache;
use MinVWS\DBCO\Encryption\Security\SecurityModule;
use MinVWS\DBCO\Encryption\Security\SimSecurityModule;
use MinVWS\DBCO\Encryption\Security\StorageTerm;
use MinVWS\MessagingApp\Action\Action;
use MinVWS\MessagingApp\Factory\LoggerFactory;
use MinVWS\MessagingApp\Helpers\CodeGenerator;
use MinVWS\MessagingApp\Helpers\HashHelper;
use MinVWS\MessagingApp\Queue\QueueClient;
use MinVWS\MessagingApp\Queue\RedisQueueClient;
use MinVWS\MessagingApp\Repository\OtpCodeRepository;
use MinVWS\MessagingApp\Repository\PairingCodeRepository;
use MinVWS\MessagingApp\Service\OtpCode\OtpCodeService;
use MinVWS\MessagingApp\Service\OtpCode\OtpCodeTypeServiceFactory;
use MinVWS\MessagingApp\Service\OtpCode\Sms\SmsInterface;
use MinVWS\MessagingApp\Service\OtpCode\Sms\SmsLocalService;
use MinVWS\MessagingApp\Service\OtpCode\Sms\SmsSpryngService;
use MinVWS\MessagingApp\Service\PairingCodeService;
use MinVWS\MessagingApp\Service\SecurityService;
use Predis\Client;
use Predis\ClientInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Selective\Validation\Encoder\JsonEncoder;
use Selective\Validation\Middleware\ValidationExceptionMiddleware;
use Selective\Validation\Transformer\ErrorDetailsResultTransformer;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;
use Spryng\SpryngRestApi\Spryng;

use function DI\autowire;

return [
    // app
    App::class => function (ContainerInterface $container): App {
        AppFactory::setContainer($container);

        return AppFactory::create();
    },

    // config
    Config::class => function (): Config {
        return new Config(require __DIR__ . '/../settings.php');
    },

    // database
    Connection::class => function (ContainerInterface $container): Connection {
        $factory = new ConnectionFactory(new Container());
        $connection = $factory->make($container->get(Config::class)->get('db')->toArray());

        // Disable the query log to prevent memory issues
        $connection->disableQueryLog();

        return $connection;
    },
    ConnectionInterface::class => function (ContainerInterface $container): ConnectionInterface {
        return $container->get(Connection::class);
    },

    // error handling
    ErrorMiddleware::class => function (ContainerInterface $container): ErrorMiddleware {
        $config = $container->get(Config::class);
        $app = $container->get(App::class);
        $logger = $container->get(LoggerInterface::class);

        return new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            $config->get('error')->get('display_error_details'),
            $config->get('error')->get('log_errors'),
            $config->get('error')->get('log_error_details'),
            $logger
        );
    },

    PDO::class => function (ContainerInterface $container): PDO {
        return $container->get(Connection::class)->getPdo();
    },

    // logger
    LoggerFactory::class => function (ContainerInterface $container) {
        return new LoggerFactory(
            $container->get(Config::class)->get('logger')->get('level'),
            $container->get(Config::class)->get('logger')->get('path')
        );
    },
    // logger
    LoggerInterface::class => function (ContainerInterface $container) {
        $loggerFactory = $container->get(LoggerFactory::class);
        $logName = $container->get(Config::class)->get('logger')->get('name');
        $logChannel = $container->get(Config::class)->get('logger')->get('channel');

        switch ($logChannel) {
            case 'null':
                $loggerFactory->addNullHandler();
                break;
            case 'file':
                $loggerFactory->addFileHandler();
                break;
            case 'test':
                $loggerFactory->addTestHandler();
                break;
            case 'stderr':
            default:
                $loggerFactory->addConsoleHandler();
                break;
        }
        return $loggerFactory->createInstance($logName);
    },

    // redis
    Client::class => function (ContainerInterface $container): Client {
        $parameters = $container->get(Config::class)->get('redis')->toArray();
        $redisSentinelService = $container->get(Config::class)->get('redis-sentinel');

        if ($redisSentinelService) {
            return new Client([$parameters], [
                'replication' => 'sentinel',
                'service' => $redisSentinelService,
            ]);
        } else {
            return new Client($parameters);
        }
    },
    ClientInterface::class => function (ContainerInterface $container): ClientInterface {
        return $container->get(Client::class);
    },
    QueueClient::class => autowire(RedisQueueClient::class),

    // validation
    ValidationExceptionMiddleware::class => function (ContainerInterface $container): ValidationExceptionMiddleware {
        $factory = $container->get(ResponseFactoryInterface::class);

        return new ValidationExceptionMiddleware(
            $factory,
            new ErrorDetailsResultTransformer(),
            new JsonEncoder()
        );
    },

    // pairing-code
    PairingCodeService::class => function (ContainerInterface $container): PairingCodeService {
        return new PairingCodeService(
            $container->get(CodeGenerator::class),
            $container->get(LoggerInterface::class),
            $container->get(PairingCodeRepository::class),
            $container->get(QueueClient::class),
            (string) $container->get(Config::class)->get('pairing_code')->get('token_allowed_charachters'),
            (int) $container->get(Config::class)->get('pairing_code')->get('token_length', 6),
            (int) $container->get(Config::class)->get('pairing_code')->get('token_lifetime_in_hours', 48),
            (string) $container->get(Config::class)->get('messagebox')->get('url'),
            (string) base64_decode($container->get(Config::class)->get('pairing_code')->get('private_key')),
            (string) base64_decode($container->get(Config::class)->get('pairing_code')->get('public_key'))
        );
    },

    // otp
    OtpCodeService::class => function (ContainerInterface $container): OtpCodeService {
        return new OtpCodeService(
            $container->get(CodeGenerator::class),
            $container->get(OtpCodeRepository::class),
            $container->get(OtpCodeTypeServiceFactory::class),
            (bool) $container->get(Config::class)->get('otp')->get('test_mode'),
        );
    },
    SmsInterface::class => function (ContainerInterface $container): SmsInterface {
        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);
        $smsConfig = $container->get(Config::class)->get('sms');

        switch ($smsConfig->get('provider')) {
            case 'spryng':
                $apiKey = $smsConfig->get('spryng')->get('api_key');
                return new SmsSpryngService($smsConfig->get('spryng'), $logger, new Spryng($apiKey));
            case 'local':
            default:
                return new SmsLocalService($logger);
        }
    },
    AuditRequests::class => autowire(AuditRequests::class),
    AuditService::class => function (ContainerInterface $c): AuditService {
        $auditService = new AuditService($c->get(AuditRepository::class));
        $auditService->setService(Action::AUDIT_SERVICE);
        return $auditService;
    },

    // security
    HashHelper::class => function (ContainerInterface $container): HashHelper {
        $hashSalt = $container->get(Config::class)->get('security')->get('hash_salt');

        return new HashHelper($hashSalt);
    },
    SecurityModule::class => function (ContainerInterface $container): SecurityModule {
        $securityModuleType = $container->get(Config::class)->get('security')->get('module_type');
        if ($securityModuleType === 'sim') {
            $securitySimKeyPath = $container->get(Config::class)->get('security')->get('sim_key_path');
            return new SimSecurityModule($securitySimKeyPath);
        } else {
            return new HSMSecurityModule($container->get(LoggerInterface::class));
        }
    },
    SecurityCache::class => function (ContainerInterface $container): SecurityCache {
        $parameters = $container->get(Config::class)->get('redis-hsm')->toArray();
        $redisSentinelService = $container->get(Config::class)->get('redis-hsm-sentinel');

        if ($redisSentinelService) {
            $client = new Client([$parameters], [
                'replication' => 'sentinel',
                'service' => $redisSentinelService,
            ]);
        } else {
            $client = new Client($parameters);
        }

        return new ProxySecurityCache(new RedisSecurityCache($client));
    },
    SecurityService::class => function (ContainerInterface $container): SecurityService {
        $storageTermConfig = $container->get(Config::class)->get('security')->get('storage_term');
        return new SecurityService(
            $container->get(SecurityModule::class),
            $container->get(SecurityCache::class),
            new DateTimeHelper(),
            'Europe/Amsterdam',
            [
                StorageTerm::SHORT => [
                    'cleanUpInterval' => $storageTermConfig->get('short')->get('cleanup_interval', 3),
                    'activeInterval' => $storageTermConfig->get('short')->get('active_interval', 31),
                ],
            ],
        );
    },

    // filesystem
    FilesystemOperator::class => function (ContainerInterface $container): FilesystemOperator {
        /** @var Config $config */
        $config = $container->get(Config::class)->get('attachments');

        switch ($config->get('filesystem')) {
            case 'attachments-local':
                $adapter = new LocalFilesystemAdapter($config->get('local')->get('path'));
                break;
            case 'attachments-s3':
                $client = new S3Client([
                    'endpoint' => $config->get('s3')->get('endpoint'),
                    'use_path_style_endpoint' => true,
                    'credentials' => [
                        'key' => $config->get('s3')->get('access_key'),
                        'secret' => $config->get('s3')->get('secret_key'),
                    ],
                    'region' => $config->get('s3')->get('region'),
                    'version' => $config->get('s3')->get('version'),
                ]);

                $adapter = new AwsS3V3Adapter($client, $config->get('s3')->get('bucket'));
                break;
            default:
                throw new Exception('attachents filesystem not configured');
        }

        return new Filesystem($adapter);
    },
];
