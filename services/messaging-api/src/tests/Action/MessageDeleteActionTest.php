<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\Action;

use MinVWS\MessagingApi\Repository\MessageWriteRepository;

use function sprintf;

class MessageDeleteActionTest extends ActionTestCase
{
    public function testMessagePost(): void
    {
        $uuid = 'foo';

        $this->mock(MessageWriteRepository::class)
            ->expects($this->once())
            ->method('delete')
            ->with($uuid);

        $response = $this->deleteAuthorized(sprintf('/api/v1/messages/%s', $uuid));

        $this->assertEquals(204, $response->getStatusCode());
    }
}
