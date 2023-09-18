<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Action\Message;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Firebase\JWT\JWT;
use MinVWS\MessagingApp\Tests\Feature\Action\ActionTestCase;

use function json_decode;

use const JSON_THROW_ON_ERROR;

class MessageIndexActionTest extends ActionTestCase
{
    public function testWithPseudoBsnSingleMessage(): void
    {
        $pseudoBsn = $this->faker->uuid;
        $testNow = CarbonImmutable::instance($this->faker->dateTime);

        CarbonImmutable::setTestNow($testNow);
        JWT::$timestamp = CarbonImmutable::now()->timestamp;

        $mailbox = $this->createMailbox(['pseudoBsn' => $pseudoBsn]);
        $alias = $this->createAlias(['mailboxUuid' => $mailbox->uuid]);
        $message = $this->createMessage([
            'uuid' => $this->faker->uuid,
            'mailboxUuid' => $mailbox->uuid,
            'aliasUuid' => $alias->uuid,
            'expiresAt' => null,
        ]);
        $this->createAttachment(['messageUuid' => $message->uuid]);

        $response = $this->getAuthenticatedJson('/api/v1/messages', [], [
            'pseudoBsn' => $pseudoBsn,
        ]);

        $expected = [[
            'uuid' => $message->uuid,
            'aliasUuid' => $message->aliasUuid,
            'fromName' => $message->fromName,
            'subject' => $message->subject,
            'createdAt' => $message->createdAt->format('c'),
            'isRead' => $message->isRead(),
            'hasAttachments' => true,
        ]];

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonDataFromResponse($expected, $response);
    }

    /**
     * @dataProvider messageIsReadDataProvider
     * @group message-test
     */
    public function testMessageIsRead(?CarbonInterface $firstReadAt, bool $expectedIsRead): void
    {
        $messageUuid = $this->faker->uuid;
        $pseudoBsn = $this->faker->uuid;
        $testNow = CarbonImmutable::instance($this->faker->dateTime);

        CarbonImmutable::setTestNow($testNow);
        JWT::$timestamp = CarbonImmutable::now()->timestamp;

        $mailbox = $this->createMailbox(['pseudoBsn' => $pseudoBsn]);
        $alias = $this->createAlias(['mailboxUuid' => $mailbox->uuid]);
        $this->createMessage([
            'uuid' => $messageUuid,
            'mailboxUuid' => $mailbox->uuid,
            'aliasUuid' => $alias->uuid,
            'expiresAt' => null,
            'firstReadAt' => $firstReadAt,
        ]);

        $response = $this->getAuthenticatedJson('/api/v1/messages', [], [
            'pseudoBsn' => $pseudoBsn,
        ]);
        /** @var array $decodedResponse */
        $decodedResponse = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($messageUuid, $decodedResponse[0]['uuid']);
        $this->assertEquals($expectedIsRead, $decodedResponse[0]['isRead']);
    }

    public function messageIsReadDataProvider(): array
    {
        return [
            'isNotRead' => [null, false],
            'isRead' => [CarbonImmutable::now(), true],
        ];
    }

    public function testWithPseudoBsnMultipleMessages(): void
    {
        $pseudoBsn = $this->faker->uuid;

        $mailbox = $this->createMailbox(['pseudoBsn' => $pseudoBsn]);
        $alias = $this->createAlias(['mailboxUuid' => $mailbox->uuid]);
        $this->createMessage([
            'uuid' => 'foo',
            'mailboxUuid' => $mailbox->uuid,
            'aliasUuid' => $alias->uuid,
            'expiresAt' => null,
        ]);
        $this->createMessage([
            'uuid' => 'bar',
            'mailboxUuid' => $mailbox->uuid,
            'aliasUuid' => $alias->uuid,
            'expiresAt' => null,
        ]);
        $this->createMessage([
            'uuid' => 'baz',
            'mailboxUuid' => $mailbox->uuid,
            'aliasUuid' => $alias->uuid,
            'expiresAt' => null,

        ]);

        $response = $this->getAuthenticatedJson('/api/v1/messages', [], [
            'pseudoBsn' => $pseudoBsn,
        ]);
        $responseBody = json_decode((string) $response->getBody());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(3, $responseBody);
    }

    public function testWithPseudoBsnNoMessages(): void
    {
        $response = $this->getAuthenticatedJson('/api/v1/messages', [], [
            'pseudoBsn' => $this->faker->uuid,
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonDataFromResponse([], $response);
    }

    public function testWithAliasUuidNoMessages(): void
    {
        $aliasUuid = $this->faker->uuid;
        $testNow = CarbonImmutable::instance($this->faker->dateTime);

        CarbonImmutable::setTestNow($testNow);
        JWT::$timestamp = CarbonImmutable::now()->timestamp;

        $response = $this->getAuthenticatedJson('/api/v1/messages', [], [
            'aliasUuid' => $aliasUuid,
        ]);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testWithPseudoBsnAndAliasUuid(): void
    {
        $pseudoBsn = $this->faker->uuid;
        $aliasUuid = $this->faker->uuid;
        $testNow = CarbonImmutable::instance($this->faker->dateTime);

        CarbonImmutable::setTestNow($testNow);
        JWT::$timestamp = CarbonImmutable::now()->timestamp;

        $response = $this->getAuthenticatedJson('/api/v1/messages', [], [
            'pseudoBsn' => $pseudoBsn,
            'aliasUuid' => $aliasUuid,
        ]);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testWithoutBearerToken(): void
    {
        $response = $this->getJson('/api/v1/messages');

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testWithInvalidBearerToken(): void
    {
        $response = $this->getJson('/api/v1/messages', [
            'Authorization' => 'Bearer invalid',
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }
}
