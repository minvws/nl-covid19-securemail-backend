<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Action\Message;

use MinVWS\MessagingApp\Tests\Feature\Action\ActionTestCase;

use function array_merge;
use function sprintf;

class MessageAuthenticationPropertiesActionTest extends ActionTestCase
{
    /**
     * @dataProvider existingMessageDataProvider
     */
    public function testValidResponse(
        bool $identityRequired,
        ?string $pseudoBsn,
        ?string $phoneNumber,
        array $expectedResult,
    ): void {
        $messageUuid = $this->faker->uuid;

        $mailbox = $this->createMailbox(['pseudoBsn' => $pseudoBsn]);
        $alias = $this->createAlias(['mailboxUuid' => $mailbox->uuid]);
        $message = $this->createMessage([
            'uuid' => $messageUuid,
            'mailboxUuid' => $mailbox->uuid,
            'aliasUuid' => $alias->uuid,
            'expiresAt' => null,
            'identityRequired' => $identityRequired,
            'pseudoBsn' => $pseudoBsn,
            'phoneNumber' => $phoneNumber,
        ]);

        $response = $this->getAuthenticatedJson(
            sprintf('/api/v1/messages/authentication-properties/%s', $message->uuid),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonDataFromResponse(array_merge(['uuid' => $messageUuid], $expectedResult), $response);
    }

    public function existingMessageDataProvider(): array
    {
        return [
            'with identity required, with bsn, with phone' => [
                true,
                'foo123',
                '0612345678',
                [
                    'identityRequired' => true,
                    'hasIdentity' => true,
                    'phoneNumber' => '*******678',
                ],
            ],
            'with identity required, with bsn, without phone' => [
                true,
                'foo123',
                null,
                [
                    'identityRequired' => true,
                    'hasIdentity' => true,
                    'phoneNumber' => null,
                ],
            ],
            'with identity required, without bsn, with phone' => [
                true,
                null,
                '0612345123',
                [
                    'identityRequired' => true,
                    'hasIdentity' => false,
                    'phoneNumber' => '*******123',
                ],
            ],
            'without identity required, without bsn, with phone' => [
                false,
                'foo123',
                '0612345111',
                [
                    'identityRequired' => false,
                    'hasIdentity' => true,
                    'phoneNumber' => '*******111',
                ],
            ],
        ];
    }

    public function testNonExistingMessage(): void
    {
        $response = $this->getAuthenticatedJson('/api/v1/messages/authentication-properties/nonexisting');

        $this->assertSame(404, $response->getStatusCode());
    }
}
