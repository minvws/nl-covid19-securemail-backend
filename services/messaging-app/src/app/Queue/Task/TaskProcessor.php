<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Queue\Task;

use MinVWS\MessagingApp\Queue\Task\DTO\Dto;

interface TaskProcessor
{
    public function process(Dto $task): void;
}
