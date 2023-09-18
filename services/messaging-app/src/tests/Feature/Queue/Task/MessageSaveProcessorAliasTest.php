<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Queue\Task;

use Laminas\Config\Config;
use League\CommonMark\MarkdownConverterInterface;
use MinVWS\Audit\AuditService;
use MinVWS\MessagingApp\Enum\MessageType;
use MinVWS\MessagingApp\Queue\QueueClient;
use MinVWS\MessagingApp\Queue\Task\DTO;
use MinVWS\MessagingApp\Queue\Task\MessageSaveProcessor;
use MinVWS\MessagingApp\Repository\AliasRepository;
use MinVWS\MessagingApp\Repository\AttachmentRepository;
use MinVWS\MessagingApp\Repository\MailboxRepository;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Service\BsnService;
use MinVWS\MessagingApp\Service\TwigTemplateService;
use MinVWS\MessagingApp\Tests\Feature\CommandFeatureTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

use function base64_encode;
use function sprintf;

/**
 * @group alias
 */
class MessageSaveProcessorAliasTest extends CommandFeatureTestCase
{
    private const PSEUDO_BSN_FIXTURE = '0fb60aa4-1ef6-4f05-aec3-5b84b62cbcf4';

    public function testUpdateMessagesAndAliasWhenIdentityRequiredForSecondMail(): void
    {
        $this->getConfig()->merge(new Config(['pseudo_bsn_service' => 'local']));

        $mailboxUuid = $this->faker->uuid;
        $aliasUuid = $this->faker->uuid;
        $pseudoBsnToken = $this->faker->uuid;
        $platform = $this->faker->word;
        $platformIdentifier = $this->faker->uuid;

        $this->createMailbox([
            'uuid' => $mailboxUuid,
            'pseudoBsn' => self::PSEUDO_BSN_FIXTURE,
        ]);

        $this->createAlias([
            'mailboxUuid' => null,
            'uuid' => $aliasUuid,
            'platform' => $platform,
            'platformIdentifier' => $platformIdentifier,
        ]);

        $saveMessageDto = new DTO\SaveMessage(
            $this->faker->uuid,
            MessageType::SECURE(),
            $platform,
            $platformIdentifier,
            null,
            $this->faker->name,
            $this->faker->safeEmail,
            $this->faker->name,
            $this->faker->safeEmail,
            null,
            $this->faker->sentence,
            $this->faker->text,
            $this->faker->paragraph,
            [
                [
                    'uuid' => $this->faker->uuid,
                    'filename' => sprintf('%s.%s', $this->faker->word, $this->faker->fileExtension()),
                    'mime_type' => $this->faker->mimeType(),
                ],
            ],
            base64_encode($this->faker->sha256),
            null,
            $this->faker->boolean,
            null,
        );

        /** @var QueueClient|MockObject $queueClient */
        $queueClient = $this->mock(QueueClient::class);
        $queueClient->expects($this->exactly(2))
            ->method('pushTask')
            ->with('notification', $this->isInstanceOf(DTO\Notification::class));

        $messageProcessor = new MessageSaveProcessor(
            $this->getContainer()->get(AliasRepository::class),
            $this->getContainer()->get(AttachmentRepository::class),
            $this->getContainer()->get(AuditService::class),
            new NullLogger(),
            $this->getContainer()->get(MailboxRepository::class),
            $this->getContainer()->get(MarkdownConverterInterface::class),
            $this->getContainer()->get(MessageRepository::class),
            $this->getContainer()->get(QueueClient::class),
            $this->getContainer()->get(TwigTemplateService::class),
            $this->getContainer()->get(BsnService::class),
        );
        $messageProcessor->process($saveMessageDto);

        $this->assertDatabaseCount('message', [
            'alias_uuid' => $aliasUuid,
        ], 1);

        $saveMessageDto = new DTO\SaveMessage(
            $this->faker->uuid,
            MessageType::SECURE(),
            $platform,
            $platformIdentifier,
            null,
            $this->faker->name,
            $this->faker->safeEmail,
            $this->faker->name,
            $this->faker->safeEmail,
            null,
            $this->faker->sentence,
            $this->faker->text,
            $this->faker->paragraph,
            [],
            base64_encode($this->faker->sha256),
            null,
            $this->faker->boolean,
            $pseudoBsnToken,
        );

        $messageProcessor->process($saveMessageDto);

        $this->assertDatabaseCount('message', [
            'alias_uuid' => $aliasUuid,
            'mailbox_uuid' => $mailboxUuid,
        ], 2);

        $this->assertDatabaseCount('alias', [
            'platform_identifier' => $platformIdentifier,
            'mailbox_uuid' => $mailboxUuid,
        ], 1);

        $this->assertDatabaseHas('mailbox', [
            'uuid' => $mailboxUuid,
        ]);
    }
}
