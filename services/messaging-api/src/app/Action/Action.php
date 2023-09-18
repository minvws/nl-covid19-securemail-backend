<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Action;

use MinVWS\Audit\AuditService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

use function get_class;
use function json_encode;

abstract class Action
{
    public const AUDIT_SERVICE = 'messaging-api';
    public const AUDIT_USER_TYPE = 'messaging-api';

    protected ServerRequestInterface $request;
    protected array $requestArguments;
    protected ResponseInterface $response;

    public function __construct(
        protected LoggerInterface $logger,
        protected AuditService $auditService,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $arguments,
    ): ResponseInterface {
        $this->logger->info('Request received', [
            'class' => get_class($this),
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
        ]);

        $this->request = $request;
        $this->requestArguments = $arguments;
        $this->response = $response;

        return $this->action();
    }

    abstract protected function action(): ResponseInterface;

    protected function jsonResponse(?array $value = null): ResponseInterface
    {
        if ($value !== null) {
            $this->response->getBody()->write((string) json_encode($value));
        }

        return $this->response->withHeader('Content-type', 'application/json');
    }
}
