<?php

namespace App\Helpers;

use Jose\Easy\JWT;

interface MaxDigidTokenValidatorInterface
{
    public function validateIdToken(string $idToken, string $clientId, string $issuer): JWT;
}
