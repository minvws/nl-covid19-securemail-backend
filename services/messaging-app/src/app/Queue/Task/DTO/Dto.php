<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Queue\Task\DTO;

use JsonSerializable;

interface Dto extends JsonSerializable
{
    /**
     * @return static
     */
    public static function jsonDeserialize(array $data);
}
