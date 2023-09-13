<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Repository\Database;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use LogicException;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;

use function sprintf;

abstract class DatabaseRepository
{
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_UPDATED_AT = 'updated_at';

    protected string $table;

    public function __construct(
        private readonly ConnectionInterface $connection,
        protected LoggerInterface $logger,
    ) {
        $this->table = $this->getTable();
    }

    abstract protected function getTable(): string;

    public function isHealthy(): bool
    {
        try {
            $this->connection->select(sprintf('select 1 from %s LIMIT 1', $this->getTable()));
            return true;
        } catch (LogicException $exception) {
            return false;
        }
    }

    protected function convertDateToString(?CarbonInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return $date->format('c');
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

    protected function getBuilder(): Builder
    {
        return $this->connection->table($this->getTable());
    }
}
