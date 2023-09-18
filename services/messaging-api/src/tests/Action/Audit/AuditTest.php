<?php

declare(strict_types=1);

namespace Tests\Feature\Action\Audit;

use Laminas\Config\Config;
use MinVWS\MessagingApi\Repository\MessageWriteRepository;
use MinVWS\MessagingApi\Tests\Action\ActionTestCase;
use MinVWS\MessagingApi\Tests\TestHelper\PostMessageBodyCreator;
use Monolog\Handler\TestHandler;

use function sprintf;

/**
 * @group audit
 */
class AuditTest extends ActionTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->getConfig()->merge(new Config([
            'logger' => [
                'channel' => 'test',
            ],
        ]));
    }

    public function testPostMessageHasAuditLog(): void
    {
        $messageUuid = $this->faker->uuid;
        $this->mockUuid($messageUuid);

        $this->mock(MessageWriteRepository::class)
            ->expects($this->once())
            ->method('save');

        $response = $this->postAuthorized('/api/v1/messages', PostMessageBodyCreator::getValidMessageBody());

        $this->assertEquals(201, $response->getStatusCode());

        // Retrieve the records from the Monolog TestHandler
        /** @var TestHandler $testLogger */
        $testLogger = $this->getContainer()->get(TestHandler::class);
        $testLogger->hasInfoThatContains("MinVWS\\MessagingApi\\Action\\MessagePostAction::action");
        $testLogger->hasInfoThatContains(sprintf('"objects":[{"type":"message","identifier":"%s"}]', $messageUuid));
    }
}
