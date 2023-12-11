<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Enums\Error;

class PairingCodeInvalidException extends PairingCodeException
{
    public function report(): void
    {
        $this->error = Error::pairingCodeInvalid();
    }
}
