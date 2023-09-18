<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Action;

use Psr\Http\Message\ResponseInterface;

class PingGetAction extends Action
{
    protected function action(): ResponseInterface
    {
        $this->auditService->setEventExpected(false);

        $this->response->getBody()->write('PONG');
        return $this->response;
    }
}
