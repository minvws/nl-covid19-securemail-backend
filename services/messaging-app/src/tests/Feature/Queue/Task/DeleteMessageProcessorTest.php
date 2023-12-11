<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Queue\Task;

use MinVWS\Audit\AuditService;
use MinVWS\MessagingApp\Queue\Task\DTO;
use MinVWS\MessagingApp\Queue\Task\MessageDeleteProcessor;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class DeleteMessageProcessorTest extends FeatureTestCase
{
    public function testProcess(): void
    {
        $uuid = $this->faker->uuid;

        $deleteMessageDto = new DTO\DeleteMessage(
            $uuid
        );

        /** @var MessageRepository|MockObject $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class);
        $messageRepository->expects($this->once())
            ->method('delete')
            ->with($uuid);

        $messageProcessor = new MessageDeleteProcessor(
            new NullLogger(),
            $messageRepository,
            $this->getContainer()->get(AuditService::class)
        );
        $messageProcessor->process($deleteMessageDto);
    }
}
