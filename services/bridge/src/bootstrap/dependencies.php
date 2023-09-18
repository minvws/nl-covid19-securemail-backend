<?php

declare(strict_types=1);

use App\Application\Commands\StatusCommand;
use App\Application\Factory\LoggerFactory;
use DI\ContainerBuilder;
use Laminas\Config\Config;
use MinVWS\Bridge\Server\Commands\LaneCommand;
use MinVWS\Bridge\Server\Services\LaneService;
use Predis\Client as PredisClient;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function DI\autowire;
use function DI\get;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions(
        [
            Config::class => function () {
                return new Config(require __DIR__ . '/settings.php');
            },
            LoggerFactory::class => function (ContainerInterface $container) {
                return new LoggerFactory(
                    $container->get(Config::class)->get('logger.level'),
                    $container->get(Config::class)->get('logger.path')
                );
            },
            LoggerInterface::class => function (ContainerInterface $container) {
                $loggerFactory = $container->get(LoggerFactory::class);
                $logName = $container->get(Config::class)->get('logger.name');
                $logChannel = $container->get(Config::class)->get('logger.channel');

                switch ($logChannel) {
                    case 'null':
                        $loggerFactory->addNullHandler();
                        break;
                    case 'file':
                        $loggerFactory->addFileHandler();
                        break;
                    case 'stderr':
                    default:
                        $loggerFactory->addConsoleHandler();
                        break;
                }
                return $loggerFactory->createInstance($logName);
            },
            PredisClient::class => autowire(PredisClient::class)
                ->constructor(get('redis.parameters'), get('redis.options')),
            'messagingAppGuzzleClient' => autowire(GuzzleHttp\Client::class)
                ->constructor(get('messagingApp')),
            StatusCommand::class => autowire(StatusCommand::class)
                ->constructorParameter('messagingAppGuzzleClient', get('messagingAppGuzzleClient'))
        ]
    );

    $lanes = require(__DIR__ . '/lanes.php');
    foreach ($lanes as $lane) {
        $name = $lane['name'];
        $service =
            autowire(LaneService::class)
                ->constructorParameter('source', get("lane.{$name}.source"))
                ->constructorParameter('destination', get("lane.{$name}.destination"));
        $command =
            autowire(LaneCommand::class)
                ->constructorParameter('name', $name)
                ->constructorParameter('description', $lane['description'])
                ->constructorParameter('laneService', get("lane.{$name}.service"));
        $containerBuilder->addDefinitions([
            "lane.{$name}.source" => $lane['source'],
            "lane.{$name}.destination" => $lane['destination'],
            "lane.{$name}.service" => $service,
            "lane.{$name}.command" => $command
        ]);
    }
};
