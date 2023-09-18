<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Action;

use Psr\Http\Message\ResponseInterface;

class PingAction extends Action
{
    public function action(): ResponseInterface
    {
        $this->auditService->setEventExpected(false);

        $this->response->getBody()->write('PONG');

        return $this->response;
    }
}
