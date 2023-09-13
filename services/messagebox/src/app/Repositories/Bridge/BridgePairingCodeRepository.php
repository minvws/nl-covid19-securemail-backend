<?php

declare(strict_types=1);

namespace App\Repositories\Bridge;

use App\Models\PairingCode;
use App\Repositories\EntityNotFoundException;
use App\Repositories\PairingCodeRepository;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class BridgePairingCodeRepository extends BridgeRepository implements PairingCodeRepository
{
    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByEmailAddressAndPairingCode(string $emailAddress, string $pairingCode): PairingCode
    {
        try {
            $response = $this->request('pairing-code', [
                'emailAddress' => $emailAddress,
                'pairingCode' => $pairingCode,
            ]);
        } catch (BridgeRequestException $bridgeRequestException) {
            $this->logger->debug('pairing-code request error', [
                'code' => $bridgeRequestException->getCode(),
                'error' => $bridgeRequestException->getMessage(),
            ]);

            switch ($bridgeRequestException->getCode()) {
                case 404:
                    throw EntityNotFoundException::fromThrowable($bridgeRequestException);
                default:
                    throw new RepositoryException($bridgeRequestException->getMessage(), 500, $bridgeRequestException);
            }
        }

        $pairingCode = $this->convertToPairingCode($response);
        $this->logger->debug('pairing-code request success', [
            'pairingCode' => $pairingCode,
        ]);

        return $pairingCode;
    }

    public function getByUuid(string $uuid): PairingCode
    {
        try {
            $response = $this->request('pairing-code-by-uuid', [], [
                'uuid' => $uuid,
            ]);
        } catch (BridgeRequestException $bridgeRequestException) {
            $this->logger->debug('pairing-code request error', [
                'code' => $bridgeRequestException->getCode(),
                'error' => $bridgeRequestException->getMessage(),
            ]);

            if ($bridgeRequestException->getCode() == 404) {
                throw EntityNotFoundException::fromThrowable($bridgeRequestException);
            } else {
                throw new RepositoryException($bridgeRequestException->getMessage(), 500, $bridgeRequestException);
            }
        }

        $pairingCode = $this->convertToPairingCode($response);
        $this->logger->debug('pairing-code-by-uuid request success', [
            'pairingCode' => $pairingCode,
        ]);

        return $pairingCode;
    }

    /**
     * @throws RepositoryException
     */
    public function renew(string $pairingCodeUuid): void
    {
        try {
            $this->request('pairing-code-renew', [
                'pairingCodeUuid' => $pairingCodeUuid,
            ]);
        } catch (BridgeRequestException $bridgeRequestException) {
            $this->logger->debug('pairing-code renew error', [
                'code' => $bridgeRequestException->getCode(),
                'error' => $bridgeRequestException->getMessage(),
            ]);

            throw new RepositoryException($bridgeRequestException->getMessage(), 500, $bridgeRequestException);
        }
    }

    private function convertToPairingCode(object $response): PairingCode
    {
        return new PairingCode(
            $response->uuid,
            $response->messageUuid,
            $response->emailAddress,
            $response->toName,
            $this->convertDate($response->validUntil),
        );
    }
}
