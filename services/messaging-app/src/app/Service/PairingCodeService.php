<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service;

use Carbon\CarbonImmutable;
use Exception;
use MinVWS\MessagingApp\Enum\QueueList;
use MinVWS\MessagingApp\Helpers\CodeGenerator;
use MinVWS\MessagingApp\Model\Message;
use MinVWS\MessagingApp\Model\PairingCode;
use MinVWS\MessagingApp\Queue\QueueClient;
use MinVWS\MessagingApp\Queue\Task\DTO;
use MinVWS\MessagingApp\Repository\EntityNotFoundException;
use MinVWS\MessagingApp\Repository\PairingCodeRepository;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\EncryptionException;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use SecureMail\Shared\Application\Helpers\EncryptionHelper;

use function sprintf;
use function urlencode;

class PairingCodeService
{
    private CodeGenerator $codeGenerator;
    private LoggerInterface $logger;
    private PairingCodeRepository $pairingCodeRepository;
    private QueueClient $queueClient;
    private string $tokenAllowedCharachters;
    private int $tokenLength;
    private int $tokenLifetimeInHours;
    private string $messageboxUrl;
    private string $pairingCodePrivateKey;
    private string $pairingCodePublicKey;

    public function __construct(
        CodeGenerator $codeGenerator,
        LoggerInterface $logger,
        PairingCodeRepository $pairingCodeRepository,
        QueueClient $queueClient,
        string $tokenAllowedCharachters,
        int $tokenLength,
        int $tokenLifetimeInHours,
        string $messageboxUrl,
        string $pairingCodePrivateKey,
        string $pairingCodePublicKey,
    ) {
        $this->codeGenerator = $codeGenerator;
        $this->logger = $logger;
        $this->pairingCodeRepository = $pairingCodeRepository;
        $this->queueClient = $queueClient;
        $this->tokenAllowedCharachters = $tokenAllowedCharachters;
        $this->tokenLength = $tokenLength;
        $this->tokenLifetimeInHours = $tokenLifetimeInHours;
        $this->messageboxUrl = $messageboxUrl;
        $this->pairingCodePrivateKey = $pairingCodePrivateKey;
        $this->pairingCodePublicKey = $pairingCodePublicKey;
    }

    /**
     * @throws PairingCodeException
     */
    public function generateForMessage(Message $message): PairingCode
    {
        $this->logger->info('generating new pairingCode for Message', ['messageUuid' => $message->uuid]);

        try {
            try {
                $pairingCode = $this->pairingCodeRepository->getByMessageUuid($message->uuid);
                $this->logger->debug('found existing pairingCode', ['pairingCodeUuid' => $pairingCode->uuid]);

                $pairingCode->previousCode = $pairingCode->code;
                $pairingCode->code = $this->generateCode();
                $pairingCode->validUntil = $this->generateValidUntil();
            } catch (EntityNotFoundException) {
                if ($message->aliasUuid === null) {
                    throw new PairingCodeException('message has no attached alias');
                }

                $pairingCode = $this->generateNew($message->aliasUuid, $message->uuid);
            }

            $this->pairingCodeRepository->save($pairingCode);
        } catch (RepositoryException $repositoryException) {
            throw PairingCodeException::fromThrowable($repositoryException);
        }

        return $pairingCode;
    }

    /**
     * @throws PairingCodeException
     */
    public function generateMessageboxUrl(?PairingCode $pairingCode = null): string
    {
        if ($pairingCode === null) {
            return $this->messageboxUrl;
        }

        try {
            $code = EncryptionHelper::encrypt(
                $this->pairingCodePrivateKey,
                $this->pairingCodePublicKey,
                $pairingCode->uuid
            );
        } catch (EncryptionException $encryptionException) {
            throw PairingCodeException::fromThrowable($encryptionException);
        }

        $this->logger->debug('generating messageboxUrl', [
            'privateKey' => $this->pairingCodePrivateKey,
            'publicKey' => $this->pairingCodePublicKey,
            'pairingCodeUuid' => $pairingCode->uuid,
            'code' => $code,
        ]);

        // yes, double encode
        return sprintf('%s/inloggen/code/%s', $this->messageboxUrl, urlencode(urlencode($code)));
    }

    /**
     * @throws PairingCodeException
     */
    public function renew($pairingCodeUuid): void
    {
        $this->logger->debug('attempting to renew pairingCode', [
            'pairingCodeUuid' => $pairingCodeUuid,
        ]);

        try {
            $pairingCode = $this->pairingCodeRepository->getByUuid($pairingCodeUuid);
        } catch (RepositoryException $repositoryException) {
            $this->logger->debug('pairingCode lookup failed');
            throw PairingCodeException::fromThrowable($repositoryException);
        }

        if ($pairingCode->validUntil->isFuture()) {
            $this->logger->debug('pairingCode has not expired');
            throw new PairingCodeException('pairingCode has not expired');
        }

        if ($pairingCode->aliasUuid === null) {
            throw new PairingCodeException('pairingCode has no attached alias');
        }

        if ($pairingCode->messageUuid === null) {
            throw new PairingCodeException('pairingCode has no attached message');
        }

        $this->queueClient->pushTask(
            QueueList::NOTIFICATION(),
            new DTO\Notification($pairingCode->messageUuid, $pairingCode->aliasUuid)
        );
    }

    /**
     * @throws PairingCodeException
     */
    private function generateNew(string $aliasUuid, string $messagUuid): PairingCode
    {
        $code = $this->generateCode();
        $validUntil = $this->generateValidUntil();

        $pairingCode = new PairingCode(UuidService::generate(), $aliasUuid, $messagUuid, $code, $validUntil);
        $this->logger->debug('generated new pairingCode', ['pairingCodeUuid' => $pairingCode->uuid]);

        return $pairingCode;
    }

    /**
     * @throws PairingCodeException
     */
    private function generateCode(): string
    {
        try {
            return $this->codeGenerator->generate($this->tokenAllowedCharachters, $this->tokenLength);
        } catch (Exception $exception) {
            throw PairingCodeException::fromThrowable($exception);
        }
    }

    private function generateValidUntil(): CarbonImmutable
    {
        return CarbonImmutable::now()->addHours($this->tokenLifetimeInHours);
    }
}
