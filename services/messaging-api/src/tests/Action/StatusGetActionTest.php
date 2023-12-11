<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\Action;

use MinVWS\MessagingApi\Repository\MessageReadRepository;
use MinVWS\MessagingApi\Repository\MessageWriteRepository;

class StatusGetActionTest extends ActionTestCase
{
    /**
     * @dataProvider statusDataProvider
     */
    public function testStatus(bool $mysqlStatus, bool $redisStatus, bool $healthStatus, int $statusCode): void
    {
        $this->mock(MessageReadRepository::class)
            ->expects($this->once())
            ->method('isHealthy')
            ->willReturn($mysqlStatus);
        $this->mock(MessageWriteRepository::class)
            ->expects($this->once())
            ->method('isHealthy')
            ->willReturn($redisStatus);

        $expectedResponse = [
            'isHealthy' => $healthStatus,
            'results' => [
                'private-mysql' => [
                    'isHealthy' => $mysqlStatus,
                ],
                'private-redis' => [
                    'isHealthy' => $redisStatus,
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
            'all healthy' => [true, true, true, 200],
            'mysql down' => [false, true, false, 503],
            'redis down' => [true, false, false, 503],
            'both down' => [false, false, false, 503],
        ];
    }
}
