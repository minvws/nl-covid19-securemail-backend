<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\Action;

use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use JsonException;
use MinVWS\MessagingApi\Tests\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

abstract class ActionTestCase extends TestCase
{
    protected function delete(string $uri, array $body = [], array $headers = []): ResponseInterface
    {
        $request = $this->createRequest('DELETE', $uri, $headers, $body);

        return $this->app->handle($request);
    }

    protected function deleteAuthorized(string $uri, array $body = [], array $headers = []): ResponseInterface
    {
        $headers = $this->addAuthorizationHeader($headers);

        return $this->delete($uri, $body, $headers);
    }

    protected function getAuthorized(string $uri, array $queryParams = [], array $headers = []): ResponseInterface
    {
        $headers = $this->addAuthorizationHeader($headers);

        return $this->get($uri, $queryParams, $headers);
    }

    protected function get(string $uri, array $queryParams = [], array $headers = []): ResponseInterface
    {
        $request = $this->createRequest('GET', $uri, $headers, [], $queryParams);

        return $this->app->handle($request);
    }

    protected function postAuthorized(string $uri, array $body = [], array $headers = []): ResponseInterface
    {
        $headers = $this->addAuthorizationHeader($headers);

        return $this->post($uri, $body, $headers);
    }

    protected function post(string $uri, array $body = [], array $headers = []): ResponseInterface
    {
        $request = $this->createRequest('POST', $uri, $headers, $body);

        return $this->app->handle($request);
    }

    protected function assertJsonDataFromResponse(array $expected, ResponseInterface $response): void
    {
        $body = (string) $response->getBody();

        try {
            $this->assertSame($expected, json_decode($body, true, 512, JSON_THROW_ON_ERROR));
        } catch (JsonException $jsonException) {
            $this->fail($jsonException->getMessage());
        }
    }

    protected function getTimestamp(int $addSeconds = 0): int
    {
        return (int) CarbonImmutable::now()->addSeconds($addSeconds)->timestamp;
    }

    private function addAuthorizationHeader(array $headers): array
    {
        JWT::$timestamp = CarbonImmutable::now()->timestamp;
        $token = JWT::encode([
            'iat' => CarbonImmutable::now()->timestamp,
            'exp' => CarbonImmutable::now()->addSeconds(60)->timestamp,
        ], 'jwt-secret', 'HS256', 'platform-identifier'); // values are set in phpunit.xml file

        $headers['Authorization'] = sprintf('Bearer %s', $token);

        return $headers;
    }

    /**
     * @param string[] $headers
     */
    private function createRequest(
        string $method,
        string $uri,
        array $headers = [],
        array $body = [],
        array $queryParams = [],
    ): ServerRequestInterface {
        $serverRequest = new ServerRequestFactory();
        $request = $serverRequest->createServerRequest($method, $uri, []);

        foreach ($headers as $name => $value) {
             $request = $request->withHeader($name, $value);
        }
        $request = $request->withParsedBody($body);
        $request = $request->withQueryParams($queryParams);

        return $request;
    }
}
