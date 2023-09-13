<?php

declare(strict_types=1);

use MinVWS\MessagingApp\Action\Attachment;
use MinVWS\MessagingApp\Action\Message;
use MinVWS\MessagingApp\Action\OtpCode;
use MinVWS\MessagingApp\Action\PairingCode;
use MinVWS\MessagingApp\Action\PingAction;
use MinVWS\MessagingApp\Action\StatusAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        return $response;
    });

    $app->group('/api/v1', function (RouteCollectorProxy $group) {
        $group->group('/attachments', function (RouteCollectorProxy $group) {
            $group->get('/{attachmentUuid}', Attachment\AttachmentViewAction::class);
        });

        $group->group('/messages', function (RouteCollectorProxy $group) {
            $group->get('', Message\MessageIndexAction::class);
            $group->get('/authentication-properties/{messageUuid}', Message\MessageAuthenticationPropertiesAction::class);
            $group->post('/incorrect-phone', Message\MessageMarkIncorrectPhoneAction::class);
            $group->post('/link', Message\MessageLinkAction::class);
            $group->post('/unlink', Message\MessageUnlinkAction::class);
            $group->get('/{messageUuid}', Message\MessageViewAction::class);
        });

        $group->group('/otp-code', function (RouteCollectorProxy $group) {
            $group->get('/options', OtpCode\OtpCodeOptionsAction::class);
            $group->post('/{messageUuid}/{type}', OtpCode\OtpCodeRequestAction::class);
        });

        $group->group('/pairing-code', function (RouteCollectorProxy $group) {
            $group->post('', PairingCode\PairingCodeValidateAction::class);
            $group->post('/renew', PairingCode\PairingCodeRenewAction::class);
            $group->get('/{uuid}', PairingCode\PairingCodeViewAction::class);
        });

        $group->get('/ping', PingAction::class);
        $group->get('/status', StatusAction::class);
    });
};
