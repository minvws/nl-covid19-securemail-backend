<?php

declare(strict_types=1);

namespace SecureMail\Shared\Application\Exceptions;

use App\Models\Enums\Error;
use Exception;
use Throwable;

class AppException extends Exception
{
    protected ?Error $error = null;

    public static function fromThrowable(Throwable $throwable): static
    {
        return new static($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
    }

    public function getError(): ?Error
    {
        return $this->error;
    }
}
