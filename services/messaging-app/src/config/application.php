<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\App;

require_once __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/shared/container.php');
$containerBuilder->addDefinitions(__DIR__ . '/shared/repositories.php');
$containerBuilder->addDefinitions(__DIR__ . '/application/container.php');
$container = $containerBuilder->build();

$app = $container->get(App::class);

(require __DIR__ . '/application/routes.php')($app);
(require __DIR__ . '/application/middleware.php')($app);

return $app;
