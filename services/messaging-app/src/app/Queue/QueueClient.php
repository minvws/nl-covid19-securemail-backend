<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Queue;

use MinVWS\MessagingApp\Enum\QueueList;
use MinVWS\MessagingApp\Queue\Task\DTO\Dto;

interface QueueClient
{
    public function pushTask(QueueList $queueList, Dto $task): void;

    public function processTasks(array $taskLists, callable $callback, int $limit): bool;
}
