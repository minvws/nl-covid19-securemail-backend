<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

use function json_encode;

class JwtAuthenticationExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (JwtAuthenticationException $exception) {
            $this->logger->debug('jwt authentication failed', ['exception' => $exception]);
            $body = $this->streamFactory->createStream((string) json_encode([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ]));

            return $this->responseFactory->createResponse()
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($body);
        }
    }
}
