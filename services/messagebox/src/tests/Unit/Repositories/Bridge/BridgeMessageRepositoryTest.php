<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories\Bridge;

use App\Models\Message;
use App\Repositories\Bridge\BridgeMessageRepository;
use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use Illuminate\Encryption\Encrypter;
use MinVWS\Bridge\Client\Client;
use MinVWS\Bridge\Client\Request;
use MinVWS\Bridge\Client\Response;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\NullLogger;
use Tests\TestCase;

use function array_merge;
use function base64_encode;
use function config;
use function json_encode;
use function sprintf;

class BridgeMessageRepositoryTest extends TestCase
{
    public function testGetByUuid(): void
    {
        $appName = $this->faker->word;
        $jwtSecret = $this->faker->password;

        config()->set('app.name', $appName);
        config()->set('services.bridge.jwt_secret', $jwtSecret);
        config()->set('services.bridge.jwt_max_lifetime', 60);
        CarbonImmutable::setTestNow('2020-01-01');

        $messageUuid = $this->faker->uuid;

        /** @var Client|MockInterface $bridgeClient */
        $bridgeClient = $this->mock(
            Client::class,
            function (MockInterface $mock) use ($appName, $jwtSecret, $messageUuid) {
                $mock->shouldReceive('request')
                    ->once()
                    ->with(Mockery::on(function (Request $request) use ($appName, $jwtSecret, $messageUuid) {
                        $requestParams = $request->getParams();
                        $authorization = JWT::encode([
                            'iss' => $appName,
                            'iat' => CarbonImmutable::now()->timestamp,
                            'exp' => CarbonImmutable::now()->addSeconds(60)->timestamp,
                        ], $jwtSecret, 'HS256', 'messagebox');

                        return $requestParams['uuid'] === $messageUuid &&
                            $requestParams['Authorization'] === sprintf('Bearer %s', $authorization);
                    }))
                    ->andReturn($this->generateResponse(['uuid' => $messageUuid]));
            }
        );

        $bridgeMessageRepository = new BridgeMessageRepository($bridgeClient, new NullLogger());
        $message = $bridgeMessageRepository->getByUuid($messageUuid);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals($messageUuid, $message->uuid);
    }

    public function testGetByUuidWithAttachments(): void
    {
        $messageUuid = $this->faker->uuid;
        $attachment1Uuid = $this->faker->uuid;
        $attachment2Uuid = $this->faker->uuid;

        $attachments = [
            [
                'uuid' => $attachment1Uuid,
                'name' => $this->faker->word,
                'mime_type' => $this->faker->mimeType(),
            ],
            [
                'uuid' => $attachment2Uuid,
                'name' => $this->faker->word,
                'mime_type' => $this->faker->mimeType(),
            ],
        ];

        /** @var Client|MockInterface $bridgeClient */
        $bridgeClient = $this->mock(Client::class, function (MockInterface $mock) use ($attachments) {
            $mock->shouldReceive('request')
                ->once()
                ->andReturn($this->generateResponse([
                    'attachments' => $attachments,
                ]));
        });

        $bridgeMessageRepository = new BridgeMessageRepository($bridgeClient, new NullLogger());
        $message = $bridgeMessageRepository->getByUuid($messageUuid);

        $this->assertEquals($attachment1Uuid, $message->attachments->get(0)->uuid);
        $this->assertEquals($attachment2Uuid, $message->attachments->get(1)->uuid);
    }

    private function generateResponse(array $responseData = []): Response
    {
        $fakerResponseData = [
            'uuid' => $this->faker->uuid,
            'aliasUuid' => $this->faker->uuid,
            'fromName' => $this->faker->company,
            'toName' => $this->faker->name,
            'subject' => $this->faker->sentence,
            'text' => $this->faker->paragraph,
            'footer' => $this->faker->word,
            'createdAt' => $this->faker->dateTime->format('c'),
            'expiresAt' => $this->faker->optional()->dateTime?->format('c'),
            'attachments' => [
                [
                    'uuid' => $this->faker->uuid,
                    'name' => $this->faker->word,
                    'mime_type' => $this->faker->mimeType(),
                ],
            ],
            'attachmentsEncryptionKey' => base64_encode(Encrypter::generateKey('aes-128-cbc')),
        ];

        return new Response(json_encode(array_merge($fakerResponseData, $responseData)));
    }
}
