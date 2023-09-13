<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Action\Message;

use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Tests\Feature\Action\ActionTestCase;
use MinVWS\MessagingApp\Tests\TestHelper\AliasFactory;
use MinVWS\MessagingApp\Tests\TestHelper\MessageFactory;
use PHPUnit\Framework\MockObject\MockObject;

class MessageUnlinkActionTest extends ActionTestCase
{
    public function testUnlinkMessageUuid(): void
    {
        /** @var MessageRepository|MockObject $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class);

        $alias = AliasFactory::create();
        $message = MessageFactory::generateModel(['aliasUuid' => $alias->uuid]);

        $messageUuid = $this->faker->uuid;
        $reason = $this->faker->word;

        $messageRepository->expects($this->once())
            ->method('getByUuid')
            ->with($messageUuid)
            ->willReturn($message);
        $messageRepository->expects($this->once())
            ->method('updateMailboxUuidByAliasUuid')
            ->with(null, $message->aliasUuid);

        $response = $this->postAuthenticated('/api/v1/messages/unlink', [
            'messageUuid' => $messageUuid,
            'reason' => $reason,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
