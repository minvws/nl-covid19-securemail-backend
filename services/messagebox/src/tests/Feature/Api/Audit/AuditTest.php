<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Audit;

use App\Models\User;
use App\Repositories\MessageRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Monolog\Handler\TestHandler;
use Tests\Feature\ControllerTestCase;

use function sprintf;

/**
 * @group audit
 */
class AuditTest extends ControllerTestCase
{
    public function testGetMessageByUuidHasAuditLog(): void
    {
        $this->config->set('feature.markdownEnabled', false);

        $pseudoBsn = $this->faker->uuid;
        $message = $this->createMessage();

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($message, $pseudoBsn) {
            $mock->shouldReceive('getByUuidAndPseudoBsn')
                ->with($message->uuid, $pseudoBsn)
                ->once()
                ->andReturn($message);
        });

        $user = new User(User::AUTH_DIGID, $pseudoBsn);
        $response = $this
            ->be($user)
            ->get(sprintf('api/v1/messages/%s', $message->uuid));

        $this->assertEquals(200, $response->getStatusCode());

        // Retrieve the records from the Monolog TestHandler
        /** @var TestHandler $testLogger */
        $testLogger = $this->app->get(TestHandler::class);
        $testLogger->hasInfoThatContains("App\\Http\\Controllers\\Api\\MessageController@getByUuid");
        $testLogger->hasInfoThatContains(sprintf('"users":[{"type":"messagebox","identifier":"%s","details":{"type":"digid"},"ip":"192.168.32.1"}],', $pseudoBsn));
        $testLogger->hasInfoThatContains(sprintf('"objects":[{"type":"message","identifier":"%s"}]', $message->uuid));
    }

    public function testGetMessageListHasAuditLog(): void
    {
        $pseudoBsn = $this->faker->uuid;

        $messageUuid1 = $this->faker->uuid;
        $messageUuid2 = $this->faker->uuid;

        $this->mock(
            MessageRepository::class,
            function (MockInterface $mock) use ($messageUuid1, $messageUuid2, $pseudoBsn) {
                $mock->shouldReceive('getByPseudoBsn')
                    ->once()
                    ->with($pseudoBsn)
                    ->andReturn(new Collection([
                        $this->createMessagePreview($messageUuid1, Carbon::now()->subDay()),
                        $this->createMessagePreview($messageUuid2, Carbon::now()),
                    ]));
            }
        );

        $user = new User(User::AUTH_DIGID, $pseudoBsn);
        $response = $this
            ->be($user)
            ->get('api/v1/messages');
        $this->assertEquals(200, $response->getStatusCode());

        // Retrieve the records from the Monolog TestHandler
        /** @var TestHandler $testLogger */
        $testLogger = $this->app->get(TestHandler::class);
        $testLogger->hasInfoThatContains("App\\Http\\Controllers\\Api\\MessageController@getList");
        $testLogger->hasInfoThatContains(sprintf('"users":[{"type":"messagebox","identifier":"%s","details":{"type":"digid"},"ip":"192.168.32.1"}],', $pseudoBsn));
        $testLogger->hasInfoThatContains(sprintf('"objects":[{"type":"message","identifier":"%s"},{"type":"message","identifier":"%s"}]', $messageUuid1, $messageUuid2));
    }
}
