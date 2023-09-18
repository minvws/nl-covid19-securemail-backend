<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Cache\CacheManager;

use function config;
use function json_decode;

class MaxConfigurationService
{
    public function __construct(
        protected MaxDigidRequestService $requestService,
        protected CacheManager $cache,
    ) {
    }

    public function getOidcConfiguration(): array
    {
        return $this->cache->remember(
            'oidcConfiguration',
            600,
            function () {
                $serverUri = config('services.max.issuerUri') . '/.well-known/openid-configuration';

                return json_decode($this->requestService->getRequest($serverUri), true);
            }
        );
    }

    public function getJwks(): array
    {
        return $this->cache->remember(
            'jwks',
            600,
            function () {
                return json_decode($this->requestService->getRequest($this->getOidcConfiguration()['jwks_uri']), true);
            }
        );
    }
}
