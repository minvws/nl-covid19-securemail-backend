<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Action\Message;

use MinVWS\MessagingApp\Repository\AliasRepository;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Repository\PairingCodeRepository;
use MinVWS\MessagingApp\Tests\Feature\Action\ActionTestCase;
use MinVWS\MessagingApp\Tests\TestHelper\AliasFactory;
use MinVWS\MessagingApp\Tests\TestHelper\MessageFactory;
use PHPUnit\Framework\MockObject\MockObject;

class MessageLinkActionTest extends ActionTestCase
{
    public function testLinkMailboxUuidAndMessageUuid(): void
    {
        /** @var AliasRepository|MockObject $aliasRepository */
        $aliasRepository = $this->mock(AliasRepository::class);

        /** @var MessageRepository|MockObject $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class);

        /** @var PairingCodeRepository|MockObject $pairingCodeRepository */
        $pairingCodeRepository = $this->mock(PairingCodeRepository::class);

        $alias = AliasFactory::create();
        $message = MessageFactory::generateModel(['aliasUuid' => $alias->uuid]);

        $mailboxUuid = $this->faker->uuid;
        $messageUuid = $this->faker->uuid;

        $messageRepository->expects($this->once())
            ->method('getByUuid')
            ->with($messageUuid)
            ->willReturn($message);
        $aliasRepository->expects($this->once())
            ->method('getByUuid')
            ->with($message->aliasUuid)
            ->willReturn($alias);
        $aliasRepository->expects($this->once())
            ->method('save')
            ->with($this->identicalTo($alias));

        $pairingCodeRepository->expects($this->once())
            ->method('deleteByMailboxUuid')
            ->with($mailboxUuid);

        $messageRepository->expects($this->once())
            ->method('updateMailboxUuidByAliasUuid')
            ->with($mailboxUuid, $alias->uuid);

        $response = $this->postAuthenticated('/api/v1/messages/link', [
            'mailboxUuid' => $mailboxUuid,
            'messageUuid' => $messageUuid,
        ]);

        $this->assertEquals(201, $response->getStatusCode());
    }
}
