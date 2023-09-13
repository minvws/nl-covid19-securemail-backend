<?php

declare(strict_types=1);

return [
    'pseudo_bsn_service' => env('PSEUDO_BSN_SERVICE', 'mittens'),
    'mittens' => [
        'client_options' => [
            'base_uri' => env('MITTENS_BASE_URI'),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'cert' => env('MITTENS_CLIENT_SSL_CERT'),
            'ssl_key' => env('MITTENS_CLIENT_SSL_KEY'),
        ],
        'digid_access_token' => env('MITTENS_DIGID_ACCESS_TOKEN'),
        'mock' => [
            'encryption' => [
                'public_key' => env('MITTENS_MOCK_ENCRYPTION_PUBLIC_KEY'),
                'private_key' => env('MITTENS_MOCK_ENCRYPTION_PRIVATE_KEY'),
            ],
        ],
    ],
    'bridge' => [
        // Since the token only has to be valid for a single request to the bridge, you can use a short lifetime here
        'jwt_max_lifetime' => env('JWT_MAX_LIFETIME', 60),
        'jwt_secret' => env('JWT_SECRET'),
    ],
    'max' => [
        'responseType' => env('DIGID_RESPONSE_TYPE'),
        'clientId' => env('DIGID_CLIENT_ID'),
        'redirectUri' => env('DIGID_REDIRECT_URI'),
        'issuerUri' => env('DIGID_ISSUER_URI'),
        'scope' => env('DIGID_SCOPE'),
        'issuer' => env('DIGID_ISSUER')
    ],
];
