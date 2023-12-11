<?php

declare(strict_types=1);

return [
    'pairing_code' => [
        'private_key' => env('PAIRING_CODE_MESSAGEBOX_PRIVATE_KEY'),
        'public_key' => env('PAIRING_CODE_MESSAGING_APP_PUBLIC_KEY'),
    ],
];
