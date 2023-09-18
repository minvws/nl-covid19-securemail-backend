<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Selective\Validation\Exception\ValidationException;

class TokenService
{
    private LoggerInterface $logger;
    private ValidationService $validationService;

    public function __construct(
        LoggerInterface $logger,
        ValidationService $validationService,
    ) {
        $this->logger = $logger;
        $this->validationService = $validationService;
    }

    /**
     * @throws ValidationException
     */
    public function getAttributeFromToken(
        ServerRequestInterface $request,
        string $attribute,
        bool $required = true,
    ) {
        $token = $request->getAttribute('token');
        if ($token === null) {
            $this->logger->debug('invalid token');
            throw new ValidationException('Invalid token');
        }
        $this->logger->debug('token found', ['token' => $token]);

        return $this->validationService->getValueFromArray($token, $attribute, $required);
    }
}
