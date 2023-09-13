<?php

declare(strict_types=1);

namespace App\Repositories\Bridge;

use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use MinVWS\Bridge\Client\Client as BridgeClient;
use MinVWS\Bridge\Client\Exception\BridgeException;
use MinVWS\Bridge\Client\Exception\RequestException;
use MinVWS\Bridge\Client\Request as BridgeRequest;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function config;
use function count;
use function json_decode;
use function json_encode;
use function property_exists;
use function sprintf;

abstract class BridgeRepository
{
    public function __construct(
        private readonly BridgeClient $client,
        protected LoggerInterface $logger,
    ) {
    }

    public function isHealthy(): bool
    {
        return $this->client->isHealty();
    }

    /**
     * @throws BridgeRequestException
     * @throws RepositoryException
     */
    protected function request(string $key, array $body = [], array $parameters = [], array $jwtPayload = []): object
    {
        $parameters['Authorization'] = $this->getAuthorizationParameter($jwtPayload);

        $request = BridgeRequest::create($key);
        $request->setResponseKey(Str::random(40));
        if (count($body) > 0) {
            $request->setData(json_encode($body));
        }
        $request->setTimeout(60);

        try {
            $this->logger->debug('Executing bridge request', [
                'key' => $key,
                'parameters' => $parameters,
                'body' => $body,
            ]);

            foreach ($parameters as $name => $value) {
                $request->setParam($name, $value);
            }
            $response = $this->client->request($request);
            $body = (object) json_decode($response->getData());
            $this->logger->debug('bridge request success', ['body' => $body]);

            return $body;
        } catch (RequestException $requestException) {
            $body = (object) json_decode($requestException->getResponse()->getData());
            $this->logger->debug('bridgeRequest exception', [
                'message' => $requestException->getMessage(),
                'body' => $body,
            ]);

            if (property_exists($body, 'code') && property_exists($body, 'error')) {
                throw new BridgeRequestException($body->error, $body->code);
            }

            throw BridgeRequestException::fromThrowable($requestException);
        } catch (BridgeException $bridgeException) {
            $this->logger->debug('bridge exception', ['message' => $bridgeException->getMessage()]);
            throw RepositoryException::fromThrowable($bridgeException);
        }
    }

    protected function convertDate(string $date): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('c', $date);
    }

    private function getAuthorizationParameter(array $payload = []): string
    {
        $now = CarbonImmutable::now();

        $payload['iss'] = config('app.name');
        $payload['iat'] = $now->timestamp;
        $payload['exp'] = $now->addSeconds(config('services.bridge.jwt_max_lifetime'))->timestamp;

        $this->logger->debug('generating jwt token', ['payload' => $payload]);

        $token = JWT::encode($payload, config('services.bridge.jwt_secret'), 'HS256', 'messagebox');

        return sprintf('Bearer %s', $token);
    }
}
