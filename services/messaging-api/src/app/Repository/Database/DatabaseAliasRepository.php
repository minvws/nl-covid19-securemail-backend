<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Repository\Database;

use Carbon\CarbonInterface;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\QueryException;
use MinVWS\MessagingApi\Enum\AliasStatus;
use MinVWS\MessagingApi\Model\GetAlias;
use MinVWS\MessagingApi\Repository\AliasReadRepository;
use MinVWS\MessagingApi\Repository\EntityNotFoundException;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function is_object;
use function sprintf;

class DatabaseAliasRepository extends DatabaseRepository implements AliasReadRepository
{
    public const TABLE = 'alias';
    public const FIELD_MAILBOX_UUID = 'mailbox_uuid';
    public const FIELD_MAILBOX_DIGID_IDENTIFIER = 'mailbox_digid_identifier';
    public const FIELD_UUID = 'uuid';

    public function getTable(): string
    {
        return self::TABLE;
    }

    /**
     * @throws RepositoryException
     */
    public function countStatusUpdates(CarbonInterface $since): int
    {
        try {
            $builder = $this->getBuilder();
            $builder->where(self::FIELD_UPDATED_AT, '>', $this->convertDateToString($since));
            return $builder->count();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }
    }

    /**
     * @return GetAlias[]
     *
     * @throws RepositoryException
     */
    public function getStatusUpdates(CarbonInterface $since, ?int $limit): array
    {
        try {
            $builder = $this->getBuilder();
            $builder->addSelect(sprintf('%s.*', self::TABLE));
            $builder->addSelect(sprintf(
                '%s.%s AS %s',
                DatabaseMailboxRepository::TABLE,
                DatabaseMailboxRepository::FIELD_DIGID_IDENTIFIER,
                self::FIELD_MAILBOX_DIGID_IDENTIFIER
            ));
            $builder->leftJoin(
                sprintf('%s AS mailbox', DatabaseMailboxRepository::TABLE),
                static function (JoinClause $join): void {
                    $join->on(
                        sprintf('%s.%s', self::TABLE, self::FIELD_MAILBOX_UUID),
                        '=',
                        sprintf('mailbox.%s', DatabaseMailboxRepository::FIELD_UUID)
                    );
                }
            );
            $builder->where(
                sprintf('%s.%s', self::TABLE, self::FIELD_UPDATED_AT),
                '>',
                $this->convertDateToString($since)
            );
            $builder->orderBy(sprintf('%s.%s', self::TABLE, self::FIELD_UPDATED_AT));

            if ($limit !== null) {
                $builder = $builder->limit($limit);
            }

            $results = $builder->get();
        } catch (QueryException $queryException) {
            throw RepositoryException::fromThrowable($queryException);
        }

        $alias = [];
        foreach ($results as $result) {
            if (is_object($result)) {
                $alias[] = $this->convertToAlias($result);
            }
        }

        return $alias;
    }

    /**
     * @throws EntityNotFoundException
     * @throws RepositoryException
     */
    private function convertToAlias(?object $result): GetAlias
    {
        if ($result === null) {
            throw new EntityNotFoundException('Alias not found');
        }

        return new GetAlias(
            $result->{self::FIELD_UUID},
            $this->getStatus($result),
            $this->convertStringToDate($result->{self::FIELD_UPDATED_AT}, true),
            $result->{self::FIELD_MAILBOX_DIGID_IDENTIFIER},
        );
    }

    private function getStatus(object $result): AliasStatus
    {
        if ($result->{self::FIELD_MAILBOX_DIGID_IDENTIFIER} !== null) {
            return AliasStatus::VERIFIED();
        }

        return AliasStatus::NEW();
    }
}
