<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Repository\Database;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use MinVWS\MessagingApp\Model\Mailbox;
use MinVWS\MessagingApp\Repository\EntityNotFoundException;
use MinVWS\MessagingApp\Repository\MailboxRepository;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function sprintf;

class DatabaseMailboxRepository extends DatabaseRepository implements MailboxRepository
{
    public const TABLE = 'mailbox';
    public const FIELD_PSEUDO_BSN = 'pseudo_bsn';
    public const FIELD_UUID = 'uuid';

    protected function getTable(): string
    {
        return self::TABLE;
    }

    public function deleteExpired(): void
    {
        try {
            $builder = $this->getBuilder();
            $builder->whereNotExists(function (Builder $builder) {
                $builder->from(DatabaseAliasRepository::TABLE);
                $builder->whereColumn(
                    sprintf(
                        '%s.%s',
                        DatabaseAliasRepository::TABLE,
                        DatabaseAliasRepository::FIELD_MAILBOX_UUID,
                    ),
                    sprintf('%s.%s', self::TABLE, self::FIELD_UUID),
                );
            });
            $builder->whereNotExists(function (Builder $builder) {
                $builder->from(DatabaseMessageRepository::TABLE);
                $builder->whereColumn(
                    sprintf(
                        '%s.%s',
                        DatabaseMessageRepository::TABLE,
                        DatabaseMessageRepository::FIELD_MAILBOX_UUID,
                    ),
                    sprintf('%s.%s', self::TABLE, self::FIELD_UUID),
                );
            });
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
    public function getByPseudoBsn(string $pseudoBsn): Mailbox
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_PSEUDO_BSN, '=', $pseudoBsn);
            $result = $builder->first();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToMailbox($result);
    }

    public function getByUuid(string $uuid): Mailbox
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_UUID, '=', $uuid);
            $result = $builder->first();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }

        return $this->convertToMailbox($result);
    }

    /**
     * @throws RepositoryException
     */
    public function save(Mailbox $mailbox): void
    {
        try {
            $builder = $this->getBuilder();
            $builder->insert([
                self::FIELD_UUID => $mailbox->uuid,
                self::FIELD_PSEUDO_BSN => $mailbox->pseudoBsn,
                self::FIELD_UPDATED_AT => CarbonImmutable::now(),
            ]);
            $this->logger->debug('mailbox saved', ['mailboxUuid' => $mailbox->uuid]);
        } catch (QueryException $queryException) {
            $this->logger->debug('mailbox save failed', ['error' => $queryException->getMessage()]);
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @throws EntityNotFoundException
     */
    private function convertToMailbox(?object $result): Mailbox
    {
        if ($result === null) {
            throw new EntityNotFoundException('Mailbox not found');
        }

        return new Mailbox(
            $result->{self::FIELD_UUID},
            $result->{self::FIELD_PSEUDO_BSN},
        );
    }
}
