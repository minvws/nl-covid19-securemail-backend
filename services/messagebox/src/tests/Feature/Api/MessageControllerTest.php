<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Attachment;
use App\Models\MessagePreview;
use App\Models\User;
use App\Repositories\MessageRepository;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\Feature\ControllerTestCase;

use function json_decode;
use function sprintf;

class MessageControllerTest extends ControllerTestCase
{
    public function testGetListWithSingleMessage(): void
    {
        $pseudoBsn = $this->faker->uuid;

        $uuid = $this->faker->uuid;
        $fromName = $this->faker->name;
        $subject = $this->faker->sentence;
        $createdAt = CarbonImmutable::instance($this->faker->dateTime);
        $isRead = $this->faker->boolean;
        $hasAttachments = $this->faker->boolean;

        $messagePreview = new MessagePreview(
            $uuid,
            $fromName,
            $subject,
            $createdAt,
            $isRead,
            $hasAttachments,
        );

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($messagePreview) {
            $mock->shouldReceive('getByPseudoBsn')
                ->once()
                ->andReturn(new Collection([$messagePreview]));
        });

        $user = new User(User::AUTH_DIGID, $pseudoBsn);
        $response = $this
            ->be($user)
            ->get('api/v1/messages');

        $expectedResponse = [[
            'uuid' => $uuid,
            'fromName' => $fromName,
            'subject' => $subject,
            'createdAt' => $createdAt->format('c'),
            'isRead' => $isRead,
            'hasAttachments' => $hasAttachments,
         ]];

        $this->assertEquals($expectedResponse, $response->json());
    }

    public function testGetListWithPseudoBsn(): void
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

        $responseBodyContent = json_decode((string) $response->getContent());
        $this->assertCount(2, $responseBodyContent);

        $this->assertEquals($messageUuid2, $responseBodyContent[0]->uuid);
        $this->assertEquals($messageUuid1, $responseBodyContent[1]->uuid);
    }

    public function testGetListWithAliasUuid(): void
    {
        $aliasUuid = $this->faker->uuid;

        $messageUuid1 = $this->faker->uuid;
        $messageUuid2 = $this->faker->uuid;

        $this->mock(
            MessageRepository::class,
            function (MockInterface $mock) use ($messageUuid1, $messageUuid2, $aliasUuid) {
                $mock->shouldReceive('getByAliasUuid')
                    ->once()
                    ->with($aliasUuid)
                    ->andReturn(new Collection([
                        $this->createMessagePreview($messageUuid1, Carbon::now()),
                        $this->createMessagePreview($messageUuid2, Carbon::now()->subDay()),
                    ]));
            }
        );

        $user = new User(User::AUTH_OTP, $aliasUuid);
        $response = $this
            ->be($user)
            ->get('api/v1/messages');
        $this->assertEquals(200, $response->getStatusCode());

        $responseBodyContent = json_decode((string) $response->getContent());
        $this->assertCount(2, $responseBodyContent);

        $this->assertEquals($messageUuid1, $responseBodyContent[0]->uuid);
        $this->assertEquals($messageUuid2, $responseBodyContent[1]->uuid);
    }


    public function testGetListWithoutPseudoBsnAndAliasUuid(): void
    {
        $response = $this
            ->get('api/v1/messages');
        $this->assertEquals(200, $response->getStatusCode());

        $responseBodyContent = json_decode((string) $response->getContent());
        $this->assertCount(0, $responseBodyContent);
    }

    public function testGetByUuid(): void
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

        $expectedResponseBodyContent = (object) [
            'message' => (object) [
                'uuid' => $message->uuid,
                'fromName' => $message->fromName,
                'toName' => $message->toName,
                'subject' => $message->subject,
                'text' => $message->text,
                'footer' => $message->footer,
                'createdAt' => $message->createdAt->format('c'),
                'expiresAt' => $message->expiresAt?->format('c'),
                'attachments' => $message->attachments->map(function (Attachment $attachment): object {
                    return (object) [
                       'uuid' => $attachment->uuid,
                       'name' => $attachment->name,
                    ];
                })->toArray(),
            ],
        ];
        $responseBodyContent = json_decode((string) $response->getContent());

        $this->assertEquals($expectedResponseBodyContent, $responseBodyContent);
    }

    public function testGetByUuidWithMarkdownEnabled(): void
    {
        $this->config->set('feature.markdownEnabled', true);

        $pseudoBsn = $this->faker->uuid;
        $message = $this->createMessage(['uuid' => $this->faker->uuid], 0);

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

        $expectedResponseBodyContent = (object) [
            'message' => (object) [
                'uuid' => $message->uuid,
                'fromName' => $message->fromName,
                'toName' => $message->toName,
                'subject' => $message->subject,
                'text' => $this->convertMarkdownToHtml($message->text),
                'footer' => $this->convertMarkdownToHtml($message->footer),
                'createdAt' => $message->createdAt->format('c'),
                'expiresAt' => $message->expiresAt?->format('c'),
                'attachments' => $message->attachments->toArray(),
            ],
        ];

        $responseBodyContent = json_decode((string) $response->getContent());
        $this->assertEquals($expectedResponseBodyContent, $responseBodyContent);
    }

    public function testUnlinkByUuid(): void
    {
        $messageUuid1 = $this->faker->uuid;
        $messageUuid2 = $this->faker->uuid;

        $this->mock(MessageRepository::class, function (MockInterface $mock) use ($messageUuid1, $messageUuid2) {
            $mock->shouldReceive('unlinkMessageByUuid')
                ->with($messageUuid1, $messageUuid2)
                ->once();
        });

        $response = $this->postJson('api/v1/messages/unlink', [
            'messageUuid' => $messageUuid1,
            'reason' => $messageUuid2,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
