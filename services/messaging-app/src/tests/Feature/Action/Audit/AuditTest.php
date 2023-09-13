<?php

declare(strict_types=1);

namespace Tests\Feature\Action\Audit;

use Carbon\CarbonImmutable;
use Illuminate\Encryption\Encrypter;
use Laminas\Config\Config;
use MinVWS\MessagingApp\Tests\Feature\Action\ActionTestCase;
use Monolog\Handler\TestHandler;

use function sprintf;

/**
 * @group audit
 */
class AuditTest extends ActionTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->getConfig()->merge(new Config([
            'logger' => [
                'channel' => 'test',
            ],
        ]));
    }

    public function testPostMessageHasAuditLog(): void
    {
        $messageUuid = $this->faker->uuid;
        $pseudoBsn = $this->faker->uuid;

        $mailbox = $this->createMailbox(['pseudoBsn' => $pseudoBsn]);
        $alias = $this->createAlias(['mailboxUuid' => $mailbox->uuid]);
        $message = $this->createMessage([
            'uuid' => $messageUuid,
            'aliasUuid' => $alias->uuid,
            'mailboxUuid' => $mailbox->uuid,
            'expiresAt' => CarbonImmutable::tomorrow(),
            'attachmentsEncryptionKey' => Encrypter::generateKey('aes-128-cbc'),
        ]);

        $jwtPayload['pseudoBsn'] = $pseudoBsn;
        $response = $this->getAuthenticatedJson(sprintf('/api/v1/messages/%s', $messageUuid), [], $jwtPayload);

        $this->assertEquals(200, $response->getStatusCode());

        // Retrieve the records from the Monolog TestHandler
        /** @var TestHandler $testLogger */
        $testLogger = $this->getContainer()->get(TestHandler::class);
        $testLogger->hasInfoThatContains("MinVWS\\MessagingApp\\Action\\MessageViewAction::action");
        $testLogger->hasInfoThatContains(sprintf('"objects":[{"type":"message","identifier":"%s"}]', $message->uuid));
    }
}
