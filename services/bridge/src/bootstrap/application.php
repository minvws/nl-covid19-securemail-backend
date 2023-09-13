<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Symfony\Component\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Set up settings
$settings = require __DIR__ . '/settings.php';
$containerBuilder->addDefinitions($settings);

// Set up dependencies
$dependencies = require __DIR__ . '/dependencies.php';
$dependencies($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Instantiate the app
$app = new Application();

// Register commands
$commands = require __DIR__ . '/commands.php';
$commands($app, $container);

return $app;
