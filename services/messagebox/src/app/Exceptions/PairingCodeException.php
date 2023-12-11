<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Enums\Error;
use SecureMail\Shared\Application\Exceptions\AppException;

class PairingCodeException extends AppException
{
    public function report(): void
    {
        $this->error = Error::pairingCodeExpired();
    }
}
