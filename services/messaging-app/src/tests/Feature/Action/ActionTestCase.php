<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Action;

use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use JsonException;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

abstract class ActionTestCase extends FeatureTestCase
{
    public const AUTHENTICATED_MESSAGE_UUID = 'valid_message_uuid';
    public const AUTHENTICATED_USER_UUID = 'valid_user_uuid';

    /**
     * @param string[] $headers
     */
    protected function getJson(string $uri, array $headers = [], ?array $data = null): ResponseInterface
    {
        $request = $this->createJsonRequest('GET', $uri, $data, $headers);

        return $this->app->handle($request);
    }

    /**
     * @param string[] $headers
     */
    protected function getAuthenticatedJson(
        string $uri,
        array $headers = [],
        array $jwtPayload = [],
        ?array $data = null,
    ): ResponseInterface {
        $headers['Authorization'] = sprintf('Bearer %s', $this->generateToken($jwtPayload));

        return $this->getJson($uri, $headers, $data);
    }

    protected function get(string $uri): ResponseInterface
    {
        $request = $this->createRequest('GET', $uri);

        return $this->app->handle($request);
    }

    protected function post(string $uri, $body = [], array $headers = []): ResponseInterface
    {
        $request = $this->createRequest('POST', $uri, $headers, $body);

        return $this->app->handle($request);
    }

    protected function postAuthenticated(string $uri, $body = [], array $headers = []): ResponseInterface
    {
        $headers['Authorization'] = sprintf('Bearer %s', $this->generateToken());

        return $this->post($uri, $body, $headers);
    }

    protected function generateToken(array $payload = [], array $headers = []): string
    {
        $payload['iat'] = $this->getTimestamp();
        $payload['exp'] = $this->getTimestamp(60);

        JWT::$timestamp = CarbonImmutable::now()->timestamp;
        return JWT::encode($payload, 'messagebox_jwt_secret', 'HS256', 'messagebox', $headers);
    }

    protected function getTimestamp(int $addSeconds = 0): int
    {
        return (int) CarbonImmutable::now()->addSeconds($addSeconds)->timestamp;
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

    /**
     * @param string[] $headers
     */
    private function createJsonRequest(
        string $method,
        string $uri,
        array $data = null,
        array $headers = [],
    ): ServerRequestInterface {
        $request = $this->createRequest($method, $uri, $headers);

        if ($data !== null) {
            $request = $request->withParsedBody($data);
        }

        return $request->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param string[] $headers
     */
    private function createRequest(
        string $method,
        string $uri,
        array $headers = [],
        array $body = [],
    ): ServerRequestInterface {
        $serverRequest = new ServerRequestFactory();
        $request = $serverRequest->createServerRequest($method, $uri, []);

        foreach ($headers as $name => $value) {
             $request = $request->withHeader($name, $value);
        }
        $request = $request->withParsedBody($body);

        return $request;
    }
}
