<?php

namespace App\Services;

use App\Exceptions\MaxDigidUnexpectedException;
use App\Providers\Auth\MaxDigidClient;
use Exception;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;

class MaxDigidRequestService
{
    public function __construct(
        private readonly MaxDigidClient $client,
    ) {
    }

    /**
     * @throws Exception
     */
    public function getRequest(string $uri, string $authorizationHeader = null): string
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];
        if (!empty($authorizationHeader)) {
            $headers['Authorization'] = 'Bearer ' . $authorizationHeader;
        }
        $request = new Request('GET', $uri, $headers);
        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new Exception('Unexpected response (' . $e->getCode() . '): ' . $e->getMessage());
        }

        if ($response->getStatusCode() >= 400) {
            throw new MaxDigidUnexpectedException(
                'Unexpected response (' . $response->getStatusCode() . '): ' . (string)$response->getBody()
            );
        }

        return (string)$response->getBody();
    }
}
