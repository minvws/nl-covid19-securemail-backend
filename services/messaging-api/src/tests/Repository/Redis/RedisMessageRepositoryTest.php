<?php

declare(strict_types=1);

namespace MinVWS\MessagingApi\Tests\Repository\Redis;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Laminas\Config\Config;
use MinVWS\MessagingApi\Enum\MessageType;
use MinVWS\MessagingApi\Model\SaveMessage;
use MinVWS\MessagingApi\Repository\Redis\RedisMessageRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Predis\ClientInterface;
use Psr\Log\NullLogger;

use function base64_encode;
use function json_encode;

class RedisMessageRepositoryTest extends RedisRepositoryTestCase
{
    public function testDelete(): void
    {
        $uuid = $this->faker->uuid;

        $redisMessageRepository = $this->getRepositoryClient('message_delete', ['uuid' => $uuid]);
        $redisMessageRepository->delete($uuid);
    }

    /**
     * @dataProvider saveMessageDataProvider
     */
    public function testSave(?CarbonInterface $aliasExpiresAt, ?CarbonInterface $expiresAt): void
    {
        $message = new SaveMessage(
            $this->faker->uuid,
            $this->faker->randomElement(MessageType::values()),
            $this->faker->word,
            $this->faker->uuid,
            $aliasExpiresAt,
            $this->faker->name,
            $this->faker->safeEmail,
            $this->faker->name,
            $this->faker->safeEmail,
            $this->faker->phoneNumber,
            $this->faker->paragraph,
            $this->faker->paragraph,
            $this->faker->paragraph,
            [base64_encode($this->faker->sentence)],
            $this->faker->sha256,
            $expiresAt,
            $this->faker->boolean,
            $this->faker->optional()->uuid,
        );

        $expectedMessageTransport = [
            'uuid' => $message->uuid,
            'type' => $message->type->getValue(),
            'platform' => $message->platform,
            'platformIdentifier' => $message->platformIdentifier,
            'aliasExpiresAt' => $aliasExpiresAt ? $message->aliasExpiresAt->format('c') : null,
            'fromName' => $message->fromName,
            'fromEmail' => $message->fromEmail,
            'toName' => $message->toName,
            'toEmail' => $message->toEmail,
            'phoneNumber' => $message->phoneNumber,
            'subject' => $message->subject,
            'text' => $message->text,
            'footer' => $message->footer,
            'attachments' => $message->attachments,
            'attachmentsEncryptionKey' => base64_encode($message->attachmentsEncryptionKey),
            'expiresAt' => $expiresAt ? $message->expiresAt->format('c') : null,
            'identityRequired' => $message->identityRequired,
            'pseudoBsnToken' => $message->pseudoBsnToken,
        ];

        $redisMessageRepository = $this->getRepositoryClient('message_save', $expectedMessageTransport);
        $redisMessageRepository->save($message);
    }

    public function saveMessageDataProvider(): array
    {
        return [
            'default' => [CarbonImmutable::now(), CarbonImmutable::now()],
            'without aliasExpiresAt' => [null, CarbonImmutable::now()],
            'without expiresAt' => [CarbonImmutable::now(), null],
            'without aliasExpiresAt and expiresAt' => [null, null],
        ];
    }

    private function getRepositoryClient(string $queueList, array $queueData): RedisMessageRepository
    {
        /** @var ClientInterface|MockObject $client */
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('__call')
            ->with('rpush', [$queueList, [json_encode($queueData)]]);

        return new RedisMessageRepository($client, $this->container->get(Config::class), new NullLogger());
    }
}
