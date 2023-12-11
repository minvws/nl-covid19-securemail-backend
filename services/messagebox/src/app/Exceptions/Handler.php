<?php

namespace App\Exceptions;

use App\Models\Enums\Error;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use SecureMail\Shared\Application\Exceptions\AppException;

use function redirect;
use function sprintf;

class Handler extends ExceptionHandler
{
    /**
     * @var array<int, string>
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (AppException $exception) {
            $error = $exception->getError() !== null ? $exception->getError() : Error::unknown();

            return redirect(sprintf('error/%s', $error->value));
        });
    }
}
