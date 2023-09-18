<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Queue\Task;

use Carbon\CarbonImmutable;
use Laminas\Config\Config;
use League\CommonMark\MarkdownConverterInterface;
use MinVWS\Audit\AuditService;
use MinVWS\MessagingApp\Enum\MessageType;
use MinVWS\MessagingApp\Model\Mailbox;
use MinVWS\MessagingApp\Queue\QueueClient;
use MinVWS\MessagingApp\Queue\Task\DTO;
use MinVWS\MessagingApp\Queue\Task\MessageSaveProcessor;
use MinVWS\MessagingApp\Repository\AliasRepository;
use MinVWS\MessagingApp\Repository\AttachmentRepository;
use MinVWS\MessagingApp\Repository\EntityNotFoundException;
use MinVWS\MessagingApp\Repository\MailboxRepository;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Service\BsnService;
use MinVWS\MessagingApp\Service\TwigTemplateService;
use MinVWS\MessagingApp\Tests\Feature\FeatureTestCase;
use MinVWS\MessagingApp\Tests\TestHelper\AliasFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

use function base64_encode;
use function sprintf;

/**
 * @group message-save-processor
 */
class MessageSaveProcessorTest extends FeatureTestCase
{
    private const PSEUDO_BSN_FIXTURE = '0fb60aa4-1ef6-4f05-aec3-5b84b62cbcf4';

    public function testProcessDirect(): void
    {
        $saveMessageDto = new DTO\SaveMessage(
            $this->faker->uuid,
            MessageType::DIRECT(),
            $this->faker->word,
            $this->faker->uuid,
            null,
            $this->faker->company,
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
            $this->faker->uuid,
        );

        /** @var QueueClient|MockObject $queueClient */
        $queueClient = $this->mock(QueueClient::class);
        $queueClient->expects($this->once())
            ->method('pushTask')
            ->with('mail', $this->isInstanceOf(DTO\Mail::class));

        /** @var MarkdownConverterInterface|MockObject $markdownConverter */
        $markdownConverter = $this->mock(MarkdownConverterInterface::class);
        $markdownConverter->expects($this->once())
            ->method('convertToHtml');

        /** @var TwigTemplateService|MockObject $twigTemplateService */
        $twigTemplateService = $this->mock(TwigTemplateService::class);
        $twigTemplateService->expects($this->once())
            ->method('render');

        $messageProcessor = new MessageSaveProcessor(
            $this->getContainer()->get(AliasRepository::class),
            $this->getContainer()->get(AttachmentRepository::class),
            $this->getContainer()->get(AuditService::class),
            new NullLogger(),
            $this->getContainer()->get(MailboxRepository::class),
            $markdownConverter,
            $this->getContainer()->get(MessageRepository::class),
            $queueClient,
            $twigTemplateService,
            $this->getContainer()->get(BsnService::class),
        );
        $messageProcessor->process($saveMessageDto);
    }

    public function testProcessSecureExistingAlias(): void
    {
        $this->setMittensConfigToLocal();

        $platform = $this->faker->word;
        $platformIdentifier = $this->faker->uuid;
        $pseudoBsn = $this->faker->uuid;
        $messageUuid = $this->faker->uuid;

        $saveMessageDto = new DTO\SaveMessage(
            $messageUuid,
            MessageType::SECURE(),
            $platform,
            $platformIdentifier,
            null,
            $this->faker->company,
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
            $pseudoBsn,
        );

        /** @var AliasRepository|MockObject $aliasRepository */
        $aliasRepository = $this->mock(AliasRepository::class);
        $alias = AliasFactory::create(['expiresAt' => null]);
        $aliasRepository->expects($this->once())
            ->method('getByPlatformIdentifier')
            ->with($platform, $platformIdentifier)
            ->willReturn($alias);
        $aliasRepository->expects($this->once())
            ->method('save');

        /** @var QueueClient|MockObject $queueClient */
        $queueClient = $this->mock(QueueClient::class);
        $queueClient->expects($this->once())
            ->method('pushTask')
            ->with('notification', new DTO\Notification($messageUuid, $alias->uuid));

        /** @var MarkdownConverterInterface|MockObject $markdownConverter */
        $markdownConverter = $this->mock(MarkdownConverterInterface::class);
        $markdownConverter->expects($this->never())
            ->method('convertToHtml');

        /** @var MessageRepository|MockObject $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class);
        $messageRepository->expects($this->once())
            ->method('save');

        /** @var TwigTemplateService|MockObject $twigTemplateService */
        $twigTemplateService = $this->mock(TwigTemplateService::class);
        $twigTemplateService->expects($this->never())
            ->method('render');

        $messageProcessor = new MessageSaveProcessor(
            $aliasRepository,
            $this->getContainer()->get(AttachmentRepository::class),
            $this->getContainer()->get(AuditService::class),
            new NullLogger(),
            $this->getContainer()->get(MailboxRepository::class),
            $markdownConverter,
            $messageRepository,
            $queueClient,
            $twigTemplateService,
            $this->getContainer()->get(BsnService::class)
        );
        $messageProcessor->process($saveMessageDto);
    }

    public function testProcessSecureExistingAliasWithUpdatedExpiresAt(): void
    {
        $this->setMittensConfigToLocal();

        $messageUuid = $this->faker->uuid;
        $platform = $this->faker->word;
        $platformIdentifier = $this->faker->uuid;
        $pseudoBsn = self::PSEUDO_BSN_FIXTURE;

        $saveMessageDto = new DTO\SaveMessage(
            $messageUuid,
            MessageType::SECURE(),
            $platform,
            $platformIdentifier,
            CarbonImmutable::now(),
            $this->faker->company,
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
            false,
            $pseudoBsn,
        );

        /** @var AliasRepository|MockObject $aliasRepository */
        $aliasRepository = $this->mock(AliasRepository::class);
        $alias = AliasFactory::create([
            'uuid' => $this->faker->uuid,
            'mailbox_uuid' => null,
            'expiresAt' => null,
        ]);
        $aliasRepository->expects($this->once())
            ->method('getByPlatformIdentifier')
            ->with($platform, $platformIdentifier)
            ->willReturn($alias);
        $aliasRepository->expects($this->exactly(2))
            ->method('save');

        /** @var QueueClient|MockObject $queueClient */
        $queueClient = $this->mock(QueueClient::class);
        $queueClient->expects($this->once())
            ->method('pushTask')
            ->with('notification', new DTO\Notification($messageUuid, $alias->uuid));

        /** @var MailboxRepository|MockObject $mailboxRepository */
        $mailboxRepository = $this->mock(MailboxRepository::class);
        $mailboxRepository->expects($this->once())
            ->method('getByPseudoBsn')
            ->with($pseudoBsn)
            ->willReturn(new Mailbox($this->faker->uuid, $pseudoBsn));

        /** @var MarkdownConverterInterface|MockObject $markdownConverter */
        $markdownConverter = $this->mock(MarkdownConverterInterface::class);
        $markdownConverter->expects($this->never())
            ->method('convertToHtml');

        /** @var MessageRepository|MockObject $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class);
        $messageRepository->expects($this->once())
            ->method('save');

        /** @var TwigTemplateService|MockObject $twigTemplateService */
        $twigTemplateService = $this->mock(TwigTemplateService::class);
        $twigTemplateService->expects($this->never())
            ->method('render');

        $messageProcessor = new MessageSaveProcessor(
            $aliasRepository,
            $this->getContainer()->get(AttachmentRepository::class),
            $this->getContainer()->get(AuditService::class),
            new NullLogger(),
            $this->getContainer()->get(MailboxRepository::class),
            $markdownConverter,
            $messageRepository,
            $queueClient,
            $twigTemplateService,
            $this->getContainer()->get(BsnService::class)
        );
        $messageProcessor->process($saveMessageDto);
    }

    public function testProcessSecureNewAlias(): void
    {
        $platform = $this->faker->word;
        $platformIdentifier = $this->faker->uuid;

        $saveMessageDto = new DTO\SaveMessage(
            $this->faker->uuid,
            MessageType::SECURE(),
            $platform,
            $platformIdentifier,
            null,
            $this->faker->company,
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
            false,
            null,
        );

        /** @var AliasRepository|MockObject $aliasRepository */
        $aliasRepository = $this->mock(AliasRepository::class);
        $aliasRepository->expects($this->once())
            ->method('getByPlatformIdentifier')
            ->with($platform, $platformIdentifier)
            ->willThrowException(new EntityNotFoundException());
        $aliasRepository->expects($this->once())
            ->method('save');

        /** @var QueueClient|MockObject $queueClient */
        $queueClient = $this->mock(QueueClient::class);
        $queueClient->expects($this->once())
            ->method('pushTask')
            ->with('notification', $this->isInstanceOf(DTO\Notification::class));

        /** @var MarkdownConverterInterface|MockObject $markdownConverter */
        $markdownConverter = $this->mock(MarkdownConverterInterface::class);
        $markdownConverter->expects($this->never())
            ->method('convertToHtml');

        /** @var MessageRepository|MockObject $messageRepository */
        $messageRepository = $this->mock(MessageRepository::class);
        $messageRepository->expects($this->once())
            ->method('save');

        /** @var TwigTemplateService|MockObject $twigTemplateService */
        $twigTemplateService = $this->mock(TwigTemplateService::class);
        $twigTemplateService->expects($this->never())
            ->method('render');

        $messageProcessor = new MessageSaveProcessor(
            $aliasRepository,
            $this->getContainer()->get(AttachmentRepository::class),
            $this->getContainer()->get(AuditService::class),
            new NullLogger(),
            $this->getContainer()->get(MailboxRepository::class),
            $markdownConverter,
            $messageRepository,
            $queueClient,
            $twigTemplateService,
            $this->getContainer()->get(BsnService::class)
        );
        $messageProcessor->process($saveMessageDto);
    }

    public function testProcessSecureWithAttachment(): void
    {
        $saveMessageDto = new DTO\SaveMessage(
            $this->faker->uuid,
            MessageType::SECURE(),
            $this->faker->word,
            $this->faker->uuid,
            null,
            $this->faker->company,
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
            false,
            null,
        );

        /** @var AttachmentRepository|MockObject $attachmentRepository */
        $attachmentRepository = $this->mock(AttachmentRepository::class);
        $attachmentRepository->expects($this->once())
            ->method('save');

        /** @var MarkdownConverterInterface|MockObject $markdownConverter */
        $markdownConverter = $this->mock(MarkdownConverterInterface::class);

        /** @var TwigTemplateService|MockObject $twigTemplateService */
        $twigTemplateService = $this->mock(TwigTemplateService::class);

        $messageProcessor = new MessageSaveProcessor(
            $this->getContainer()->get(AliasRepository::class),
            $attachmentRepository,
            $this->getContainer()->get(AuditService::class),
            new NullLogger(),
            $this->getContainer()->get(MailboxRepository::class),
            $markdownConverter,
            $this->getContainer()->get(MessageRepository::class),
            $this->getContainer()->get(QueueClient::class),
            $twigTemplateService,
            $this->getContainer()->get(BsnService::class)
        );

        $messageProcessor->process($saveMessageDto);
    }

    public function setMittensConfigToLocal(): void
    {
        $this->getConfig()->merge(new Config(['pseudo_bsn_service' => 'local']));
    }
}
