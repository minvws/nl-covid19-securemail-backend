<?php

declare(strict_types=1);

namespace SecureMail\Shared\Application\Repositories\Mittens;

use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use SecureMail\Shared\Application\Models\MittensUserInfo;
use SecureMail\Shared\Application\Models\PseudoBsn;
use SecureMail\Shared\Application\Repositories\PseudoBsnRepository;

use function count;
use function json_decode;
use function json_encode;
use function property_exists;

class MittensPseudoBsnRepository implements PseudoBsnRepository
{
    private const API_REQUEST_RETRY_COUNT = 1;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly string $accessToken,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws RepositoryException
     *
     * @todo NOT USED anymore in SecureMail side
     */
    public function getByBsn(string $bsn): string
    {
        $response = $this->post('/service/via_digid', [
            'body' => json_encode([
                'digid_access_token' => $this->accessToken,
                'BSN' => $bsn,
            ]),
        ]);

        $this->validateResponse($response);

        return $response->data[0]->guid;
    }

    /**
     * @throws RepositoryException
     */
    public function getByDigidToken(string $idToken): MittensUserInfo
    {
        $responseBody = $this->post('/service/via_max/', [
            'body' => json_encode([
                'id_token' => $idToken,
                'access_token' => $this->accessToken,
            ]),
        ]);

        $this->validateResponse($responseBody);

        $userData = $responseBody->data[0];

        return new MittensUserInfo(
            $userData->first_name,
            $userData->prefix,
            $userData->last_name,
            $userData->gender,
            $userData->guid,
            $userData->encrypted
        );
    }

    /**
     * @throws RepositoryException
     */
    public function getByToken(string $pseudoBsnToken): PseudoBsn
    {
        $responseBody = $this->post('/service/via_token/', [
            'body' => json_encode([
                'token' => $pseudoBsnToken,
                'access_token' => $this->accessToken,
            ]),
        ]);

        $this->validateResponse($responseBody);

        $bsnData = $responseBody->data[0];

        return new PseudoBsn(
            $bsnData->guid,
            $bsnData->censored_bsn,
            $bsnData->letters,
            null
        );
    }

    /**
     * @throws RepositoryException
     */
    public function post(string $uri, array $options): object
    {
        $this->logger->info(sprintf('Mittens request: %s Options: %s', $uri, json_encode($options)));
        try {
            $response = $this->client->request('POST', $uri, $options);
        } catch (ClientException $clientException) {
            $responseData = json_decode((string)$clientException->getResponse()->getBody());

            if (
                is_object($responseData)
                && property_exists($responseData, 'errors')
                && is_array($responseData->errors)
            ) {
                throw new RepositoryException($responseData->errors[0], $clientException->getCode(), $clientException);
            }
            return $this->retryRequest($clientException, $uri, $options);
        } catch (ServerException $serverException) {
            return $this->retryRequest($serverException, $uri, $options);
        } catch (GuzzleException $guzzleException) {
            throw new RepositoryException($guzzleException->getMessage(), $guzzleException->getCode(), $guzzleException);
        }

        return (object) json_decode($response->getBody()->getContents());
    }

    /**
     * @throws RepositoryException
     */
    private function retryRequest(Exception $exception, string $uri, array $options): object
    {
        if (BsnRetryRequestCounter::get() >= self::API_REQUEST_RETRY_COUNT) {
            throw new RepositoryException('Mittens service unavailable', $exception->getCode(), $exception);
        }

        //Service unavailable.. retry request
        BsnRetryRequestCounter::increment();
        return $this->post($uri, $options);
    }

    /**
     * @throws RepositoryException
     */
    protected function validateResponse(object $response): void
    {
        if (!property_exists($response, 'data')) {
            throw new RepositoryException('no data-field in response');
        }

        if (count($response->data) === 0) {
            throw new RepositoryException('no results found');
        }

        if (count($response->data) > 1) {
            throw new RepositoryException('too many results found');
        }

        if (!property_exists($response->data[0], 'guid')) {
            throw new RepositoryException('no guid-field in response');
        }
    }
}
