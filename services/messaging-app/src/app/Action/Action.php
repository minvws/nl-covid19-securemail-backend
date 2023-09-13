<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Action;

use Exception;
use MinVWS\Audit\AuditService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Selective\Validation\Exception\ValidationException;

use function get_class;
use function json_encode;

abstract class Action
{
    public const AUDIT_SERVICE = 'messaging-app';
    public const AUDIT_USER_TYPE = 'messaging-app';

    protected ServerRequestInterface $request;
    protected array $requestArguments;
    protected ResponseInterface $response;

    public function __construct(
        protected AuditService $auditService,
        protected LoggerInterface $logger,
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

        $this->logger->debug('Request body', [
            'body' => $request->getBody(),
        ]);

        $this->request = $request;
        $this->requestArguments = $arguments;
        $this->response = $response;

        try {
            return $this->action();
        } catch (ValidationException $validationException) {
            return $this->jsonResponse(['error' => $validationException->getMessage()], 422);
        } catch (Exception $exception) {
            $this->logger->error('error', ['exception' => $exception]);
            return $this->errorResponse($exception->getMessage(), 500);
        }
    }

    abstract protected function action(): ResponseInterface;

    protected function getRequestBody(): array
    {
        return (array) $this->request->getParsedBody();
    }

    protected function createdResponse(): ResponseInterface
    {
        return $this->jsonResponse()->withStatus(201);
    }

    protected function jsonResponse(array $body = [], int $statusCode = 200): ResponseInterface
    {
        $this->logger->debug('jsonResponse body', ['body' => json_encode($body)]);
        $this->response->getBody()->write(json_encode($body));
//        $this->logger->debug('jsonResponse', ['body' => $this->response->getBody()]);

        return $this->response
            ->withHeader('Content-type', 'application/json')
            ->withStatus($statusCode);
    }

    protected function notFoundResponse(): ResponseInterface
    {
        return $this->errorResponse('notFound', 404);
    }

    protected function notAllowedResponse(Exception $e): ResponseInterface
    {
        return $this->errorResponse($e->getMessage(), 403);
    }

    private function errorResponse(string $errorMessage, int $code): ResponseInterface
    {
        return $this->jsonResponse([
            'code' => $code,
            'error' => $errorMessage,
        ], $code);
    }
}
