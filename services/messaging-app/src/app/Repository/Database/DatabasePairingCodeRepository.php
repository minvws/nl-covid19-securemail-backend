<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Repository\Database;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\QueryException;
use MinVWS\MessagingApp\Model\PairingCode;
use MinVWS\MessagingApp\Repository\EntityNotFoundException;
use MinVWS\MessagingApp\Repository\PairingCodeRepository;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function sprintf;

class DatabasePairingCodeRepository extends DatabaseRepository implements PairingCodeRepository
{
    public const FIELD_ALIAS_UUID = 'alias_uuid';
    public const FIELD_CODE = 'code';
    public const FIELD_MESSAGE_UUID = 'message_uuid';
    public const FIELD_PAIRED_AT = 'paired_at';
    public const FIELD_PREVIOUS_CODE = 'previous_code';
    public const FIELD_UUID = 'uuid';
    public const FIELD_VALID_UNTIL = 'valid_until';
    public const TABLE = 'pairing_code';

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
            $builder->where(self::FIELD_VALID_UNTIL, '<', CarbonImmutable::now());
            $builder->orWhereNull([self::FIELD_ALIAS_UUID, self::FIELD_MESSAGE_UUID]);
            $builder->limit(1000);
            $builder->delete();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @throws RepositoryException
     */
    public function deleteByMailboxUuid(string $mailboxUuid): void
    {
        try {
            $builder = $this->getBuilder();
            $builder->join(
                DatabaseMessageRepository::TABLE,
                static function (JoinClause $join): void {
                    $join->on(
                        sprintf('%s.%s', DatabaseMessageRepository::TABLE, DatabaseMessageRepository::FIELD_UUID),
                        '=',
                        sprintf('%s.%s', self::TABLE, self::FIELD_MESSAGE_UUID),
                    );
                }
            );
            $builder->join(
                DatabaseMailboxRepository::TABLE,
                static function (JoinClause $join): void {
                    $join->on(
                        sprintf('%s.%s', DatabaseMailboxRepository::TABLE, DatabaseMailboxRepository::FIELD_UUID),
                        '=',
                        sprintf('%s.%s', self::TABLE, DatabaseMessageRepository::FIELD_MAILBOX_UUID),
                    );
                }
            );
            $builder->where(sprintf('%s.%s', DatabaseMailboxRepository::TABLE, self::FIELD_UUID), '=', $mailboxUuid);
            $deleteCount = $builder->delete();
            $this->logger->debug('pairingcode delete success', ['deleteCount' => $deleteCount]);
        } catch (QueryException $queryException) {
            $this->logger->debug('pairingcode update failed', ['error' => $queryException->getMessage()]);
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByEmailAddressAndCode(string $emailAddress, string $code): PairingCode
    {
        try {
            $builder = $this->getBuilder();
            $builder->select(sprintf('%s.*', self::TABLE));
            $builder->join(
                DatabaseMessageRepository::TABLE,
                static function (JoinClause $join): void {
                    $join->on(
                        sprintf('%s.%s', DatabaseMessageRepository::TABLE, DatabaseMessageRepository::FIELD_UUID),
                        '=',
                        sprintf('%s.%s', self::TABLE, self::FIELD_MESSAGE_UUID),
                    );
                }
            );
            $builder->where(DatabaseMessageRepository::FIELD_TO_EMAIL_HASH, '=', $this->hash($emailAddress));
            $builder->where(self::FIELD_CODE, '=', $code);
            $result = $builder->first();
        } catch (QueryException $queryException) {
            $this->logger->debug('getByMessageUuid failed', ['error' => $queryException->getMessage()]);
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToPairingCode($result);
    }

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    public function getByMessageUuid(string $messageUuid): PairingCode
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_MESSAGE_UUID, '=', $messageUuid);
            $result = $builder->first();
        } catch (QueryException $queryException) {
            $this->logger->debug('getByMessageUuid failed', ['error' => $queryException->getMessage()]);
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToPairingCode($result);
    }

    public function getByUuid(string $uuid): PairingCode
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_UUID, '=', $uuid);
            $result = $builder->first();
        } catch (QueryException $queryException) {
            $this->logger->debug('getByUuid failed', ['error' => $queryException->getMessage()]);
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToPairingCode($result);
    }

    /**
     * @throws RepositoryException
     */
    public function save(PairingCode $pairingCode): void
    {
        try {
            $builder = $this->getBuilder();
            $builder->updateOrInsert(
                [
                    self::FIELD_UUID => $pairingCode->uuid,
                ],
                [
                    self::FIELD_ALIAS_UUID => $pairingCode->aliasUuid,
                    self::FIELD_MESSAGE_UUID => $pairingCode->messageUuid,
                    self::FIELD_CODE => $pairingCode->code,
                    self::FIELD_PREVIOUS_CODE => $pairingCode->previousCode,
                    self::FIELD_VALID_UNTIL => $pairingCode->validUntil,
                    self::FIELD_PAIRED_AT => $pairingCode->pairedAt,
                    self::FIELD_UPDATED_AT => CarbonImmutable::now(),
                ]
            );
            $this->logger->debug('pairing_code saved', ['pairingCodeUuid' => $pairingCode->uuid]);
        } catch (QueryException $queryException) {
            $this->logger->debug('pairing_code save failed', ['error' => $queryException->getMessage()]);
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    private function convertToPairingCode(?object $result): PairingCode
    {
        if ($result === null) {
            throw new EntityNotFoundException('PairingCode not found');
        }

        $this->logger->debug('found pairing-code', [
            'uuid' => $result->{self::FIELD_UUID},
            'message_uuid' => $result->{self::FIELD_MESSAGE_UUID},
        ]);

        return new PairingCode(
            $result->{self::FIELD_UUID},
            $result->{self::FIELD_ALIAS_UUID},
            $result->{self::FIELD_MESSAGE_UUID},
            $result->{self::FIELD_CODE},
            $this->convertStringToDate($result->{self::FIELD_VALID_UNTIL}),
            $this->convertStringToDate($result->{self::FIELD_PAIRED_AT}),
            $result->{self::FIELD_PREVIOUS_CODE}
        );
    }
}
