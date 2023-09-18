<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Repository\Database;

class DatabaseMailboxRepository extends DatabaseRepository
{
    public const TABLE = 'mailbox';
    public const FIELD_DIGID_IDENTIFIER = 'digid_identifier';
    public const FIELD_UUID = 'uuid';

    public function getTable(): string
    {
        return self::TABLE;
    }
}
