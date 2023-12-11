<?php

declare(strict_types=1);

use Laminas\Config\Config;
use MinVWS\Audit\Middleware\AuditRequests;
use MinVWS\MessagingApp\Middleware\JwtAuthenticationExceptionMiddleware;
use Psr\Log\LoggerInterface;
use Selective\Validation\Middleware\ValidationExceptionMiddleware;
use Slim\App;
use Slim\Middleware\ErrorMiddleware;
use Tuupola\Middleware\JwtAuthentication;

return function (App $app) {
    $container = $app->getContainer();
    $jwtOptions = array_merge(
        $container->get(Config::class)->get('jwt')->toArray(),
        ['logger' => $container->get(LoggerInterface::class)],
    );

    $app->addBodyParsingMiddleware();
    $app->add(new JwtAuthentication($jwtOptions));
    $app->add(JwtAuthenticationExceptionMiddleware::class);
    $app->add(ValidationExceptionMiddleware::class);
    $app->addRoutingMiddleware();
    $app->add(ErrorMiddleware::class);
    $app->add(AuditRequests::class);
};
