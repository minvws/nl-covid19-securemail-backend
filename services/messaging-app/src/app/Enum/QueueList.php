<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Enum;

use MyCLabs\Enum\Enum;

class QueueList extends Enum
{
    private const MAIL = 'mail';
    private const MESSAGE_SAVE = 'message_save';
    private const MESSAGE_DELETE = 'message_delete';
    private const NOTIFICATION = 'notification';

    public static function MAIL(): self
    {
        return new QueueList(self::MAIL);
    }

    public static function MESSAGE_DELETE(): self
    {
        return new QueueList(self::MESSAGE_DELETE);
    }

    public static function MESSAGE_SAVE(): self
    {
        return new QueueList(self::MESSAGE_SAVE);
    }

    public static function NOTIFICATION(): self
    {
        return new QueueList(self::NOTIFICATION);
    }
}
