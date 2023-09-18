<?php

declare(strict_types=1);

namespace App\Repositories\Bridge;

use App\Models\Enums\LoginType;
use App\Models\OtpCode;
use App\Repositories\EntityExpiredException;
use App\Repositories\EntityNotFoundException;
use App\Repositories\OtpCodeRepository;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class BridgeOtpCodeRepository extends BridgeRepository implements OtpCodeRepository
{
    /**
     * @throws EntityExpiredException
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByMessageUuidAndOtpCode(string $messageUuid, string $otpCode): string
    {
        try {
            $response = $this->request('get-otp-code', [
                'messageUuid' => $messageUuid,
                'otpCode' => $otpCode,
            ]);
        } catch (BridgeRequestException $bridgeRequestException) {
            $this->logger->debug('get-otp-code request error', [
                'code' => $bridgeRequestException->getCode(),
                'error' => $bridgeRequestException->getMessage(),
            ]);

            throw new RepositoryException($bridgeRequestException->getMessage(), 500, $bridgeRequestException);
        }

        return $response->uuid;
    }

    /**
     * @throws RepositoryException
     */
    public function requestByTypeAndMessageUuid(LoginType $loginType, string $messageUuid): OtpCode
    {
        try {
            $response = $this->request('request-otp-code', [], [
                'type' => $loginType->value,
                'messageUuid' => $messageUuid,
            ]);
        } catch (BridgeRequestException $bridgeRequestException) {
            $this->logger->debug('request-otp-code request error', [
                'code' => $bridgeRequestException->getCode(),
                'error' => $bridgeRequestException->getMessage(),
            ]);

            throw new RepositoryException($bridgeRequestException->getMessage(), 500, $bridgeRequestException);
        }

        return $this->convertToOtpCode($response);
    }

    private function convertToOtpCode(object $response): OtpCode
    {
        return new OtpCode(
            $response->uuid,
            LoginType::forValue($response->type),
            $response->phoneNumber,
            $this->convertDate($response->validUntil),
        );
    }
}
