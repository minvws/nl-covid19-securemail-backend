<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Repository\Database;

class DatabasePairingCodeRepository extends DatabaseRepository
{
    public const TABLE = 'pairing_code';
    public const FIELD_MESSAGE_UUID = 'message_uuid';
    public const FIELD_PAIRED_AT = 'paired_at';

    public function getTable(): string
    {
        return self::TABLE;
    }
}
