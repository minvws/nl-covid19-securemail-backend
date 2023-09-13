<?php

declare(strict_types=1);

use Laminas\Config\Config;
use MinVWS\Audit\Middleware\AuditRequests;
use MinVWS\MessagingApi\Middleware\JwtAuthenticationExceptionMiddleware;
use Selective\Validation\Middleware\ValidationExceptionMiddleware;
use Slim\App;
use Slim\Middleware\ErrorMiddleware;
use Tuupola\Middleware\JwtAuthentication;

return function (App $app) {
    $app->addBodyParsingMiddleware();
    $app->add(new JwtAuthentication($app->getContainer()->get(Config::class)->get('jwt_authentication')->toArray()));
    $app->add(JwtAuthenticationExceptionMiddleware::class);
    $app->add(ValidationExceptionMiddleware::class);
    $app->addRoutingMiddleware();
    $app->add(ErrorMiddleware::class);
    $app->add(AuditRequests::class);
};
