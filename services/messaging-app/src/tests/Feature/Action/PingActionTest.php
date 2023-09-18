<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Action;

class PingActionTest extends ActionTestCase
{
    public function testPing(): void
    {
        $response = $this->get('/api/v1/ping');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('PONG', $response->getBody());
    }
}
