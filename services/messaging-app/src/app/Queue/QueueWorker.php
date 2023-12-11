<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Queue;

use JsonException;
use Laminas\Config\Config;
use MinVWS\MessagingApp\Enum\QueueList;
use MinVWS\MessagingApp\Queue\Task\DTO;
use MinVWS\MessagingApp\Queue\Task\TaskProcessorFactory;
use Psr\Log\LoggerInterface;

use function json_decode;
use function sprintf;

use const JSON_OBJECT_AS_ARRAY;
use const JSON_THROW_ON_ERROR;

class QueueWorker
{
    public function __construct(
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        private readonly QueueClient $queueClient,
        private readonly TaskProcessorFactory $taskProcessorFactory,
    ) {
    }

    public function process(int $limit): bool
    {
        $this->logger->info(sprintf('Processing tasks (max %s)', $limit));
        $taskLists = $this->loadTaskLists();

        $callback = function (QueueList $queueList, string $taskData): void {
            $this->logger->debug('callback', ['queueList' => $queueList]);
            $task = $this->convertToTask($queueList, $taskData);
            $this->processTask($queueList, $task);
        };

        return $this->queueClient->processTasks($taskLists, $callback, $limit);
    }

    /**
     * @throws QueueException
     */
    private function convertToTask(QueueList $queueList, string $taskData): DTO\Dto
    {
        $this->logger->info('converting to task', ['queueList' => $queueList->getValue()]);
        try {
            $data = json_decode($taskData, true, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
            $this->logger->info('decoded task from queue', [
                'queueList' => $queueList->getValue(),
            ]);
            $this->logger->debug('decoded task from queue');
        } catch (JsonException $jsonException) {
            throw QueueException::fromThrowable($jsonException);
        }

        switch ($queueList) {
            case $queueList->equals(QueueList::MAIL()):
                return DTO\Mail::jsonDeserialize($data);
            case $queueList->equals(QueueList::MESSAGE_DELETE()):
                return DTO\DeleteMessage::jsonDeserialize($data);
            case $queueList->equals(QueueList::MESSAGE_SAVE()):
                return DTO\SaveMessage::jsonDeserialize($data);
            case $queueList->equals(QueueList::NOTIFICATION()):
                return DTO\Notification::jsonDeserialize($data);
            default:
                throw new QueueException(sprintf('unconfigured queuelist: %s', $queueList));
        }
    }

    private function processTask(QueueList $queueList, DTO\Dto $task): void
    {
        try {
            $taskProcessor = $this->taskProcessorFactory->byQueueList($queueList);
            $this->logger->info('start process of message', [
                'queueList' => $queueList->getValue(),
            ]);
            $taskProcessor->process($task);
        } catch (QueueException $queueException) {
            $this->logger->error('task processor failed', ['exception' => $queueException]);
        }
    }

    private function loadTaskLists(): array
    {
        return [
            // ordering is important here, tasks will be processed from top to bottom
            QueueList::MESSAGE_SAVE()->getValue() => $this->getFromConfigOrDefault('message_save'),
            QueueList::MESSAGE_DELETE()->getValue() => $this->getFromConfigOrDefault('message_delete'),
            QueueList::NOTIFICATION()->getValue() => $this->getFromConfigOrDefault('notification'),
            QueueList::MAIL()->getValue() => $this->getFromConfigOrDefault('mail'),
        ];
    }

    private function getFromConfigOrDefault(string $configKey): string
    {
        /** @var Config $configRedisLists */
        $configRedisLists = $this->config->get('redis')->get('lists');

        $configValue = $configRedisLists->get($configKey);
        if ($configValue !== false) {
            return $configValue;
        }

        return $configKey;
    }
}
