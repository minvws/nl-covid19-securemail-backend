<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Action;

use MinVWS\MessagingApp\Repository\MessageRepository;

class StatusActionTest extends ActionTestCase
{
    /**
     * @dataProvider statusDataProvider
     */
    public function testStatus(bool $mysqlStatus, bool $healthStatus, int $statusCode): void
    {
        $this->mock(MessageRepository::class)
            ->expects($this->once())
            ->method('isHealthy')
            ->willReturn($mysqlStatus);

        $expectedResponse = [
            'isHealthy' => $healthStatus,
            'results' => [
                'private-mysql' => [
                    'isHealthy' => $mysqlStatus,
                ],
            ],
        ];

        $response = $this->get('/api/v1/status');

        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertJsonDataFromResponse($expectedResponse, $response);
    }

    public function statusDataProvider(): array
    {
        return [
            'all healthy' => [true, true, 200],
            'mysql down' => [false, false, 503],
        ];
    }
}
