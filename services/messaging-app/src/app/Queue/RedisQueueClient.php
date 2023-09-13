<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Queue;

use MinVWS\MessagingApp\Enum\QueueList;
use MinVWS\MessagingApp\Queue\Task\DTO\Dto;
use Predis\ClientInterface as RedisClient;
use Psr\Log\LoggerInterface;

use function count;
use function is_array;
use function json_encode;

class RedisQueueClient implements QueueClient
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RedisClient $redisClient,
    ) {
    }

    public function pushTask(QueueList $queueList, Dto $task): void
    {
        $this->logger->debug('pushing task on queue', ['queueList' => $queueList]);

        $this->redisClient->rpush($queueList->getValue(), [json_encode($task)]);
    }

    public function processTasks(array $taskLists, callable $callback, int $limit): bool
    {
        for ($i = 0; $i < $limit; $i++) {
            $blpopResponse = $this->redisClient->blpop($taskLists, 5);
            $this->logger->debug('blpopResponse', [
                'i' => $i,
                'blpopResponse' => $blpopResponse,
            ]);

            if (!is_array($blpopResponse) || count($blpopResponse) !== 2) {
                break;
            }

            $this->logger->debug('received element from queue');

            $callback(QueueList::from($blpopResponse[0]), $blpopResponse[1]);
        }

        return true;
    }
}
