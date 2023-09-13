<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Queue\Task;

use Exception;
use MinVWS\MessagingApp\Enum\QueueList;
use MinVWS\MessagingApp\Queue\QueueException;
use Psr\Log\LoggerInterface;
use Slim\App;
use Throwable;

class TaskProcessorFactory
{
    public function __construct(
        private readonly App $app,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws QueueException
     */
    public function byQueueList(QueueList $queueList): TaskProcessor
    {
        $container = $this->app->getContainer();

        try {
            switch ($queueList) {
                case QueueList::MAIL()->getValue():
                    return $container->get(MailProcessor::class);
                case QueueList::MESSAGE_DELETE()->getValue():
                    return $container->get(MessageDeleteProcessor::class);
                case QueueList::MESSAGE_SAVE()->getValue():
                    return $container->get(MessageSaveProcessor::class);
                case QueueList::NOTIFICATION()->getValue():
                    return $container->get(NotificationProcessor::class);
                default:
                    $this->logger->debug('unconfigured queuelist found', ['list' => $queueList]);
                    throw new QueueException('unconfigured queuelist found');
            }
        } catch (Exception | Throwable $exception) {
            throw QueueException::fromThrowable($exception);
        }
    }
}
