<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Repository\Database;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use LogicException;
use MinVWS\DBCO\Encryption\Security\EncryptionException;
use MinVWS\DBCO\Encryption\Security\EncryptionHelper;
use MinVWS\DBCO\Encryption\Security\StorageTerm;
use MinVWS\MessagingApp\Helpers\HashHelper;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use SodiumException;

use function sprintf;

abstract class DatabaseRepository
{
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';

    protected string $table;

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly EncryptionHelper $encryptionHelper,
        private readonly HashHelper $hashHelper,
        protected LoggerInterface $logger,
    ) {
    }

    abstract protected function getTable(): string;

    public function isHealthy(): bool
    {
        try {
            $this->connection->select(sprintf('select 1 from %s LIMIT 1', $this->getTable()));

            return true;
        } catch (LogicException) {
            return false;
        }
    }

    /**
     * @throws RepositoryException
     */
    protected function convertStringToDate(?string $date, bool $required = false): ?CarbonImmutable
    {
        if ($date === null) {
            if ($required) {
                throw new RepositoryException('required date field is null');
            }

            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d H:i:s', $date);
    }

    protected function hash(string $data): string
    {
        return $this->hashHelper->hash($data);
    }

    /**
     * @throws EncryptionException
     */
    protected function unseal(string $value): string
    {
        try {
            return $this->encryptionHelper->unsealStoreValue($value);
        } catch (SodiumException $sodiumException) {
            throw new EncryptionException(
                $sodiumException->getMessage(),
                $sodiumException->getCode(),
                $sodiumException
            );
        }
    }

    /**
     * @throws EncryptionException
     */
    protected function unsealOptional(?string $value): ?string
    {
        try {
            return $this->encryptionHelper->unsealOptionalStoreValue($value);
        } catch (SodiumException $sodiumException) {
            throw new EncryptionException(
                $sodiumException->getMessage(),
                $sodiumException->getCode(),
                $sodiumException
            );
        }
    }

    /**
     * @throws EncryptionException
     */
    protected function seal(?string $value, DateTimeInterface $referenceDateTime): ?string
    {
        return $this->encryptionHelper->sealOptionalStoreValue($value, StorageTerm::short(), $referenceDateTime);
    }

    protected function getBuilder(): Builder
    {
        return $this->connection->table($this->getTable());
    }
}
