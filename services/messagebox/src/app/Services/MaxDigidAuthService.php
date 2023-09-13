<?php

namespace App\Services;

use App\Exceptions\MaxAuthenticationCancelledException;
use App\Exceptions\MaxException;
use App\Helpers\MaxDigidTokenValidatorInterface;
use App\Helpers\OidcHelper;
use App\Models\DigidUser;
use App\Providers\Auth\MaxDigidClient;
use Carbon\CarbonImmutable;
use Exception;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Support\Facades\Session;
use Jose\Easy\JWT;
use Psr\Http\Client\ClientExceptionInterface;
use SecureMail\Shared\Application\Exceptions\BsnServiceException;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use SecureMail\Shared\Application\Repositories\PseudoBsnRepository;

use function config;
use function http_build_query;
use function is_array;
use function is_object;
use function json_decode;
use function property_exists;
use function random_bytes;
use function sprintf;
use function urlencode;

class MaxDigidAuthService
{
    private array $config;

    public function __construct(
        private readonly MaxConfigurationService $maxConfigurationService,
        private readonly PseudoBsnRepository $pseudoBsnRepository,
        private readonly MaxDigidClient $client,
        private readonly MaxDigidTokenValidatorInterface $maxDigidTokenValidator,
    ) {
        if (is_array(config('services.max'))) {
            $this->config = config('services.max');
        }
    }

    /**
     * @throws Exception
     */
    public function getAuthorizeUrl(): string
    {
        $configuration = $this->maxConfigurationService->getOidcConfiguration();
        $state = $this->getState();
        $codeVerifier = OidcHelper::getCodeVerifier();

        Session::put('openidcodeverifier', $codeVerifier);
        Session::put('openidstate', $state);

        $codeChallenge = OidcHelper::getCodeChallenge($codeVerifier);
        $nonce = OidcHelper::base64UrlEncode(random_bytes(32));

        $queryParameters = [
            'response_type' => $this->config['responseType'],
            'client_id' => $this->config['clientId'],
            'state' => $state,
            'redirect_uri' => $this->config['redirectUri'],
            'scope' => $this->config['scope'],
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'nonce' => $nonce,
        ];

        return sprintf('%s?%s', $configuration['authorization_endpoint'], http_build_query($queryParameters));
    }

    /**
     * @throws MaxException
     */
    public function requestNewAccessToken(string $code, ?string $state): string
    {
        if (Session::get('openidcodeverifier') === null) {
            throw new MaxException('code verifier is not set');
        }
        if (Session::get('openidstate') === null) {
            throw new MaxException('state is not set');
        }
        if (Session::get('openidstate') !== $state) {
            throw new MaxException('Unexpected state value');
        }
        $payload = sprintf(
            'grant_type=authorization_code&code=%s&redirect_uri=%s&code_verifier=%s&client_id=%s',
            $code,
            urlencode($this->config['redirectUri']),
            Session::get('openidcodeverifier'),
            $this->config['clientId']
        );

        $clientRequest = new GuzzleRequest('POST', sprintf('%s/accesstoken', $this->config['issuerUri']), [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ], $payload);

        try {
            $response = $this->client->sendRequest($clientRequest);
        } catch (ClientExceptionInterface $e) {
            throw new MaxException(sprintf('Error trying to fetch access token: %s', $e->getMessage()));
        }

        $accessTokenResponse = json_decode((string)$response->getBody());

        if ($response->getStatusCode() === 400 && is_object($accessTokenResponse) && property_exists($accessTokenResponse, 'error') && $accessTokenResponse->error === 'saml_authn_failed') {
            throw new MaxAuthenticationCancelledException('Digid login was cancelled by user.');
        }

        if ($response->getStatusCode() >= 400) {
            throw new MaxException(sprintf('Unable to get access token, server returned: %s', $response->getBody()));
        }

        if (is_object($accessTokenResponse) && property_exists($accessTokenResponse, 'id_token')) {
            $this->validateIdToken($accessTokenResponse->id_token);
        }

        if (!$accessTokenResponse || !isset($accessTokenResponse->id_token)) {
            throw new MaxException('Invalid response');
        }

        //Gerbrand Bosch: This should be changed to access_token when Mittens is fixed
        return $accessTokenResponse->id_token;
    }

    private function validateIdToken(string $idToken): JWT
    {
        return $this->maxDigidTokenValidator->validateIdToken(
            $idToken,
            $this->config['clientId'],
            $this->config['issuer']
        );
    }

    /**
     * @throws BsnServiceException
     */
    public function getAuthenticatedUser(string $digidToken): DigidUser
    {
        try {
            $mittensUserInfo = $this->pseudoBsnRepository->getByDigidToken($digidToken);
        } catch (RepositoryException $e) {
            throw BsnServiceException::fromThrowable($e);
        }

        return new DigidUser(
            $mittensUserInfo->firstName,
            $mittensUserInfo->prefix,
            $mittensUserInfo->lastName,
            $mittensUserInfo->gender,
            $mittensUserInfo->guid,
            $mittensUserInfo->encrypted,
            CarbonImmutable::now()
        );
    }

    protected function getState(): string
    {
        return OidcHelper::base64UrlEncode('What state do we need?');
    }
}
