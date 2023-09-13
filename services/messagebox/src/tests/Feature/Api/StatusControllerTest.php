<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Repositories\MessageRepository;
use Mockery\MockInterface;
use Tests\Feature\ControllerTestCase;

use function json_encode;

class StatusControllerTest extends ControllerTestCase
{
    public function testPing(): void
    {
        $response = $this->get('/api/v1/ping');

        $response->assertStatus(200);
        $this->assertEquals('PONG', $response->getContent());
    }

    /**
     * @dataProvider statusDataProvider
     */
    public function testStatus(bool $redisStatus, bool $healthStatus, int $statusCode): void
    {
        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($redisStatus): void {
            $mock->shouldReceive('isHealthy')
                ->once()
                ->andReturn($redisStatus);
        });

        $expectedResponse = [
            'isHealthy' => $healthStatus,
            'results' => [
                'bridge-redis' => [
                    'isHealthy' => $redisStatus,
                ],
            ],
        ];

        $response = $this->get('/api/v1/status');

        $response->assertStatus($statusCode);
        $this->assertEquals(json_encode($expectedResponse), $response->getContent());
    }

    public function statusDataProvider(): array
    {
        return [
            'all healthy' => [true, true, 200],
            'redis down' => [false, false, 503],
        ];
    }
}
