<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Queue;

use Laminas\Config\Config;
use MinVWS\MessagingApp\Queue\QueueClient;
use MinVWS\MessagingApp\Queue\QueueWorker;
use MinVWS\MessagingApp\Queue\Task\TaskProcessorFactory;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class QueueWorkerTest extends FeatureTestCase
{
    public function testProcessTaskList(): void
    {
        /** @var QueueClient|MockObject $client */
        $client = $this->createMock(QueueClient::class);
        $client->expects($this->once())
            ->method('processTasks')
            ->with([
                'message_save' => 'message_save', // set as env-var in phpunit.xml
                'message_delete' => 'message_delete', // set as env-var in phpunit.xml
                'notification' => 'notification', // NOT set as env-var in phpunit.xml
                'mail' => 'mail', // NOT set as env-var in phpunit.xml
            ]);

        $queueWorker = new QueueWorker(
            $this->getContainer()->get(Config::class),
            new NullLogger(),
            $client,
            $this->getContainer()->get(TaskProcessorFactory::class),
        );

        $queueWorker->process(10);
    }
}
