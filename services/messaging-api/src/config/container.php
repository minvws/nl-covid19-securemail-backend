<?php

declare(strict_types=1);

use Aws\S3\S3Client;
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
use MinVWS\MessagingApi\Action\Action;
use MinVWS\MessagingApi\Factory\LoggerFactory;
use Predis\Client;
use Predis\ClientInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Selective\Validation\Encoder\JsonEncoder;
use Selective\Validation\Middleware\ValidationExceptionMiddleware;
use Selective\Validation\Transformer\ErrorDetailsResultTransformer;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\Psr7\Factory\StreamFactory;

use function DI\autowire;

return [
    // app
    App::class => function (ContainerInterface $container): App {
        AppFactory::setContainer($container);

        return AppFactory::create();
    },

    // config
    Config::class => function () {
        return new Config(require __DIR__ . '/settings.php');
    },

    // response
    ResponseFactoryInterface::class => function (ContainerInterface $container): ResponseFactoryInterface {
        return $container->get(App::class)->getResponseFactory();
    },

    StreamFactoryInterface::class => function (): StreamFactory {
        return new StreamFactory();
    },

    // router
    RouteParserInterface::class => function (ContainerInterface $container): RouteParserInterface {
        return $container->get(App::class)->getRouteCollector()->getRouteParser();
    },

    // logger
    LoggerFactory::class => function (ContainerInterface $container) {
        return new LoggerFactory(
            $container->get(Config::class)->get('logger')->get('level'),
            $container->get(Config::class)->get('logger')->get('path')
        );
    },
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

    // database
    Connection::class => function (ContainerInterface $container) {
        $factory = new ConnectionFactory(new Container());

        $connection = $factory->make($container->get(Config::class)->get('db')->toArray());

        // Disable the query log to prevent memory issues
        $connection->disableQueryLog();

        return $connection;
    },
    ConnectionInterface::class => function (ContainerInterface $container) {
        return $container->get(Connection::class);
    },

    // redis
    Client::class => function (ContainerInterface $container): Client {
        $redisSentinelService = getenv('REDIS_SENTINEL_SERVICE');

        $parameters = $container->get(Config::class)->get('redis')->toArray();

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

    // validation
    ValidationExceptionMiddleware::class => function (ContainerInterface $container): ValidationExceptionMiddleware {
        $factory = $container->get(ResponseFactoryInterface::class);

        return new ValidationExceptionMiddleware(
            $factory,
            new ErrorDetailsResultTransformer(),
            new JsonEncoder()
        );
    },

    // error handling
    ErrorMiddleware::class => function (ContainerInterface $container): ErrorMiddleware {
        $app = $container->get(App::class);
        $config = $container->get(Config::class)->get('error');
        $logger = $container->get(LoggerInterface::class);

        return new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            $config->get('display_error_details'),
            $config->get('log_errors'),
            $config->get('log_error_details'),
            $logger
        );
    },

    AuditRequests::class => autowire(AuditRequests::class),
    AuditService::class => function (ContainerInterface $c) {
        $auditService = new AuditService($c->get(AuditRepository::class));
        $auditService->setService(Action::AUDIT_SERVICE);
        return $auditService;
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
