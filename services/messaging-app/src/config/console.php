<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use MinVWS\MessagingApp\Command\DatabaseFreshCommand;
use MinVWS\MessagingApp\Command\DatabasePurgeCommand;
use MinVWS\MessagingApp\Command\ManageKeysCommand;
use MinVWS\MessagingApp\Command\QueueWorkCommand;
use MinVWS\MessagingApp\Command\TestDataGenerateCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;

require __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/shared/container.php');
$containerBuilder->addDefinitions(__DIR__ . '/shared/repositories.php');
$containerBuilder->addDefinitions(__DIR__ . '/console/container.php');
$container = $containerBuilder->build();

$commandLoader = new ContainerCommandLoader($container, [
    DatabaseFreshCommand::getDefaultName() => DatabaseFreshCommand::class,
    DatabasePurgeCommand::getDefaultName() => DatabasePurgeCommand::class,
    ManageKeysCommand::getDefaultName() => ManageKeysCommand::class,
    TestDataGenerateCommand::getDefaultName() => TestDataGenerateCommand::class,
    QueueWorkCommand::getDefaultName() => QueueWorkCommand::class,
]);

$app = new Application();
$app->setCommandLoader($commandLoader);

return ['app' => $app, 'container' => $container];
