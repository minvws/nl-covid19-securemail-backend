<?php

namespace Tests\Feature\Web\Auth;

use App\Helpers\MaxDigidTokenValidatorInterface;
use Jose\Easy\JWT;

class MaxDigidTokenValidatorMock implements MaxDigidTokenValidatorInterface
{
    public function validateIdToken(string $idToken, string $clientId, string $issuer): JWT
    {
        return new JWT();
    }
}
