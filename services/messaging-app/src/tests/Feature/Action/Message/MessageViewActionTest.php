<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Action\Message;

use Carbon\CarbonImmutable;
use Illuminate\Encryption\Encrypter;
use MinVWS\MessagingApp\Tests\Feature\Action\ActionTestCase;

use function base64_encode;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

class MessageViewActionTest extends ActionTestCase
{
    public function testValidMessageResponse(): void
    {
        $messageUuid = $this->faker->uuid;
        $psuedoBsn = $this->faker->uuid;

        $mailbox = $this->createMailbox(['pseudoBsn' => $psuedoBsn]);
        $alias = $this->createAlias(['mailboxUuid' => $mailbox->uuid]);
        $message = $this->createMessage([
            'uuid' => $messageUuid,
            'aliasUuid' => $alias->uuid,
            'mailboxUuid' => $mailbox->uuid,
            'expiresAt' => CarbonImmutable::tomorrow(),
            'attachmentsEncryptionKey' => Encrypter::generateKey('aes-128-cbc'),
        ]);

        $jwtPayload['pseudoBsn'] = $psuedoBsn;
        $response = $this->getAuthenticatedJson(sprintf('/api/v1/messages/%s', $messageUuid), [], $jwtPayload);
        $expectedResponse = [
            'uuid' => $messageUuid,
            'aliasUuid' => $message->aliasUuid,
            'fromName' => $message->fromName,
            'toName' => $message->toName,
            'subject' => $message->subject,
            'text' => $message->text,
            'footer' => $message->footer,
            'createdAt' => $message->createdAt->format('c'),
            'expiresAt' => $message->expiresAt?->format('c'),
            'attachments' => [],
            'attachmentsEncryptionKey' => base64_encode($message->attachmentsEncryptionKey),
        ];

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonDataFromResponse($expectedResponse, $response);
    }

    public function testValidMessageWithAttachmentResponse(): void
    {
        $messageUuid = $this->faker->uuid;
        $psuedoBsn = $this->faker->uuid;
        $attachmentUuid = $this->faker->uuid;
        $attachmentFilename = $this->faker->word;
        $attachmentMimeType = $this->faker->mimeType();

        $mailbox = $this->createMailbox(['pseudoBsn' => $psuedoBsn]);
        $alias = $this->createAlias(['mailboxUuid' => $mailbox->uuid]);
        $this->createMessage([
            'uuid' => $messageUuid,
            'aliasUuid' => $alias->uuid,
            'mailboxUuid' => $mailbox->uuid,
            'expiresAt' => CarbonImmutable::tomorrow(),
        ]);
        $this->createAttachment([
            'uuid' => $attachmentUuid,
            'messageUuid' => $messageUuid,
            'filename' => $attachmentFilename,
            'mimeType' => $attachmentMimeType,
        ]);

        $jwtPayload['pseudoBsn'] = $psuedoBsn;
        $response = $this->getAuthenticatedJson(sprintf('/api/v1/messages/%s', $messageUuid), [], $jwtPayload);
        $expectedResponse = [
            [
                'uuid' => $attachmentUuid,
                'name' => $attachmentFilename,
                'mime_type' => $attachmentMimeType,
            ]
        ];

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($expectedResponse, $body['attachments']);
    }

    public function testNotFound(): void
    {
        $jwtPayload['pseudoBsn'] = $this->faker->uuid;
        $response = $this->getAuthenticatedJson('/api/v1/messages/unknown', [], $jwtPayload);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDigidUnauthorisedResponse(): void
    {
        $messageUuid = $this->faker->uuid;

        $mailbox = $this->createMailbox();
        $alias = $this->createAlias(['mailboxUuid' => $mailbox->uuid]);
        $this->createMessage([
            'uuid' => $messageUuid,
            'aliasUuid' => $alias->uuid,
            'mailboxUuid' => $mailbox->uuid,
            'expiresAt' => null,
        ]);

        $jwtPayload['pseudoBsn'] = 'other-pseudo-bsn';
        $response = $this->getAuthenticatedJson(sprintf('/api/v1/messages/%s', $messageUuid), [], $jwtPayload);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJsonDataFromResponse([
            'code' => 403,
            'error' => 'Current Digid account is not authorised to retrieve message',
        ], $response);
    }

    public function testOtpUnauthorisedResponse(): void
    {
        $messageUuid = $this->faker->uuid;

        $mailbox = $this->createMailbox();
        $alias = $this->createAlias([
            'mailboxUuid' => $mailbox->uuid,
            'expiresAt' => null,
        ]);
        $message = $this->createMessage([
            'uuid' => $messageUuid,
            'aliasUuid' => $alias->uuid,
            'mailboxUuid' => $mailbox->uuid,
            'expiresAt' => null,
        ]);
        $this->createOtpCode([
            'messageUuid' => $message->uuid,
            'validUntil' => CarbonImmutable::now()->addDay(),
        ]);

        $otpCode = $this->createOtpCode(['validUntil' => CarbonImmutable::now()->addDay()]);
        $jwtPayload['otpCodeUuid'] = $otpCode->uuid;
        $response = $this->getAuthenticatedJson(sprintf('/api/v1/messages/%s', $messageUuid), [], $jwtPayload);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJsonDataFromResponse([
            'code' => 403,
            'error' => 'Given otpCodeUuid is not authorised to retrieve message',
        ], $response);
    }

    public function testUnauthorisedResponse(): void
    {
        $messageUuid = $this->faker->uuid;

        $mailbox = $this->createMailbox();
        $alias = $this->createAlias(['mailboxUuid' => $mailbox->uuid]);
        $this->createMessage([
            'uuid' => $messageUuid,
            'aliasUuid' => $alias->uuid,
            'mailboxUuid' => $mailbox->uuid,
            'expiresAt' => null,
        ]);

        $response = $this->getAuthenticatedJson(sprintf('/api/v1/messages/%s', $messageUuid), [], []);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJsonDataFromResponse([
            'code' => 403,
            'error' => 'Message can only be retrieved when either an OTP or a Digid login is given.',
        ], $response);
    }

    public function testMarkRead(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::instance($this->faker->dateTime));

        $messageUuid = $this->faker->uuid;
        $pseudoBsn = $this->faker->uuid;

        $mailbox = $this->createMailbox(['pseudoBsn' => $pseudoBsn]);
        $alias = $this->createAlias(['mailboxUuid' => $mailbox->uuid]);
        $this->createMessage([
            'uuid' => $messageUuid,
            'aliasUuid' => $alias->uuid,
            'mailboxUuid' => $mailbox->uuid,
            'firstReadAt' => null,
        ]);

        $this->assertDatabaseHas('message', [
            'uuid' => $messageUuid,
            'first_read_at' => null,
        ]);

        $jwtPayload['pseudoBsn'] = $pseudoBsn;
        $this->getAuthenticatedJson(sprintf('/api/v1/messages/%s', $messageUuid), [], $jwtPayload);

        $this->assertDatabaseHas('message', [
            'uuid' => $messageUuid,
            'first_read_at' => CarbonImmutable::now(),
        ]);
    }

    public function testAuthorizationToViewMultipleWhenLoggedInWithOtpForSingleMessage(): void
    {
        $messageUuid1 = $this->faker->uuid;
        $messageUuid2 = $this->faker->uuid;

        $mailbox = $this->createMailbox();
        $alias = $this->createAlias([
            'mailboxUuid' => $mailbox->uuid,
            'expiresAt' => null,
        ]);
        $this->createMessage([
            'uuid' => $messageUuid1,
            'aliasUuid' => $alias->uuid,
            'mailboxUuid' => $mailbox->uuid,
            'expiresAt' => null,
        ]);
        $otpCode = $this->createOtpCode([
            'messageUuid' => $messageUuid1,
            'validUntil' => CarbonImmutable::now()->addDay(),
        ]);

        // now create second message for the same alias/mailbox
        $this->createMessage([
            'uuid' => $messageUuid2,
            'aliasUuid' => $alias->uuid,
            'mailboxUuid' => $mailbox->uuid,
            'expiresAt' => null,
        ]);

        $jwtPayload['otpCodeUuid'] = $otpCode->uuid;

        $response1 = $this->getAuthenticatedJson(sprintf('/api/v1/messages/%s', $messageUuid1), [], $jwtPayload);
        $this->assertEquals(200, $response1->getStatusCode());

        // second message should also be accessible
        $response2 = $this->getAuthenticatedJson(sprintf('/api/v1/messages/%s', $messageUuid2), [], $jwtPayload);
        $this->assertEquals(200, $response2->getStatusCode());
    }

    public function testAuthorizationNotAllowedWhenLoggedInWithOtpForSingleMessage(): void
    {
        $messageUuid1 = $this->faker->uuid;
        $messageUuid2 = $this->faker->uuid;

        $mailbox = $this->createMailbox();
        $alias = $this->createAlias([
            'mailboxUuid' => $mailbox->uuid,
            'expiresAt' => null,
        ]);
        $this->createMessage([
            'uuid' => $messageUuid1,
            'aliasUuid' => $alias->uuid,
            'mailboxUuid' => $mailbox->uuid,
            'expiresAt' => null,
        ]);
        $otpCode = $this->createOtpCode([
            'messageUuid' => $messageUuid1,
            'validUntil' => CarbonImmutable::now()->addDay(),
        ]);

        // now create second message for other alias/mailbox
        $this->createMessage([
            'uuid' => $messageUuid2,
            'expiresAt' => null,
        ]);

        $jwtPayload['otpCodeUuid'] = $otpCode->uuid;

        $response1 = $this->getAuthenticatedJson(sprintf('/api/v1/messages/%s', $messageUuid1), [], $jwtPayload);
        $this->assertEquals(200, $response1->getStatusCode());

        // second message should NOT be accessible
        $response2 = $this->getAuthenticatedJson(sprintf('/api/v1/messages/%s', $messageUuid2), [], $jwtPayload);
        $this->assertEquals(403, $response2->getStatusCode());
    }
}
