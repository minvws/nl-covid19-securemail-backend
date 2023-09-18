<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Repository\Database;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use MinVWS\MessagingApp\Model\Alias;
use MinVWS\MessagingApp\Repository\AliasRepository;
use MinVWS\MessagingApp\Repository\EntityNotFoundException;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

class DatabaseAliasRepository extends DatabaseRepository implements AliasRepository
{
    public const TABLE = 'alias';
    public const FIELD_EXPIRES_AT = 'expires_at';
    public const FIELD_EMAIL_ADDRESS = 'email_address';
    public const FIELD_MAILBOX_UUID = 'mailbox_uuid';
    public const FIELD_PLATFORM = 'platform';
    public const FIELD_PLATFORM_IDENTIFIER = 'platform_identifier';
    public const FIELD_UUID = 'uuid';

    protected function getTable(): string
    {
        return self::TABLE;
    }

    /**
     * @throws RepositoryException
     */
    public function deleteExpired(): void
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_EXPIRES_AT, '<', CarbonImmutable::now());
            $builder->orWhereNull('mailbox_uuid');
            $builder->limit(1000);
            $builder->delete();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByPlatformIdentifier(string $platform, string $platformIdentifier): Alias
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_PLATFORM, '=', $platform);
            $builder->where(self::FIELD_PLATFORM_IDENTIFIER, '=', $platformIdentifier);
            $result = $builder->first();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToAlias($result);
    }

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByUuid(string $uuid): Alias
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_UUID, '=', $uuid);
            $builder->where(static function (Builder $builder): void {
                $builder->whereNull(self::FIELD_EXPIRES_AT);
                $builder->orWhere(self::FIELD_EXPIRES_AT, '>', CarbonImmutable::now());
            });
            $result = $builder->first();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToAlias($result);
    }

    /**
     * @throws RepositoryException
     */
    public function save(Alias $alias): void
    {
        try {
            $builder = $this->getBuilder();
            $builder->updateOrInsert([self::FIELD_UUID => $alias->uuid], [
                self::FIELD_MAILBOX_UUID => $alias->mailboxUuid,
                self::FIELD_PLATFORM => $alias->platform,
                self::FIELD_PLATFORM_IDENTIFIER => $alias->platformIdentifier,
                self::FIELD_EXPIRES_AT => $alias->expiresAt,
                self::FIELD_EMAIL_ADDRESS => $this->seal($alias->emailAddress, $alias->createdAt),
                self::FIELD_CREATED_AT => $alias->createdAt,
                self::FIELD_UPDATED_AT => CarbonImmutable::now(),
            ]);
            $this->logger->debug('alias saved', ['aliasUuid' => $alias->uuid]);
        } catch (QueryException $queryException) {
            $this->logger->debug('alias save failed', ['error' => $queryException->getMessage()]);
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    private function convertToAlias(?object $result): Alias
    {
        if ($result === null) {
            throw new EntityNotFoundException('Alias not found');
        }

        return new Alias(
            $result->{self::FIELD_UUID},
            $result->{self::FIELD_MAILBOX_UUID},
            $result->{self::FIELD_PLATFORM},
            $result->{self::FIELD_PLATFORM_IDENTIFIER},
            $this->convertStringToDate($result->{self::FIELD_EXPIRES_AT}),
            $this->unseal($result->{self::FIELD_EMAIL_ADDRESS}),
            $this->convertStringToDate($result->{self::FIELD_CREATED_AT}, true),
        );
    }
}
