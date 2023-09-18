<?php

declare(strict_types=1);

use MinVWS\MessagingApi\Action\AliasGetStatusUpdatesAction;
use MinVWS\MessagingApi\Action\MessageDeleteAction;
use MinVWS\MessagingApi\Action\MessageGetStatusUpdatesAction;
use MinVWS\MessagingApi\Action\MessagePostAction;
use MinVWS\MessagingApi\Action\PingGetAction;
use MinVWS\MessagingApi\Action\StatusGetAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        return $response;
    });

    $app->get('/api/v1/ping', PingGetAction::class);
    $app->get('/api/v1/status', StatusGetAction::class);

    $app->get('/api/v1/aliases/statusupdates', AliasGetStatusUpdatesAction::class);

    $app->post('/api/v1/messages', MessagePostAction::class);
    $app->get('/api/v1/messages/statusupdates', MessageGetStatusUpdatesAction::class);
    $app->delete('/api/v1/messages/{uuid}', MessageDeleteAction::class);
};
