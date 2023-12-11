<?php

namespace App\Helpers;

use App\Services\MaxConfigurationService;
use Jose\Component\Core\JWK;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use function app;

class MaxDigidTokenValidator implements MaxDigidTokenValidatorInterface
{
    public function validateIdToken(string $idToken, string $clientId, string $issuer): JWT
    {
        $jwks = app(MaxConfigurationService::class)->getJwks();
        $key = new JWK($jwks['keys'][0]);
        $jws = Load::jws($idToken)
            ->algs(['RS256'])
            ->exp()
            ->aud($clientId)
            ->iss($issuer)
            ->key($key);

        //Make sure libgmp-dev package and gmp php-extention are installed
        return $jws->run();
    }
}
