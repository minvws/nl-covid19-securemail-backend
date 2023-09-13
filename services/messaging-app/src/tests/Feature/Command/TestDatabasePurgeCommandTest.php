<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Command;

use MinVWS\MessagingApp\Repository\AliasRepository;
use MinVWS\MessagingApp\Repository\MailboxRepository;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Repository\OtpCodeRepository;
use MinVWS\MessagingApp\Repository\PairingCodeRepository;
use MinVWS\MessagingApp\Service\AttachmentException;
use MinVWS\MessagingApp\Service\AttachmentService;
use MinVWS\MessagingApp\Tests\Feature\CommandFeatureTestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function sprintf;

class TestDatabasePurgeCommandTest extends CommandFeatureTestCase
{
    public function testCommand(): void
    {
        $aliasRepository = $this->mock(AliasRepository::class);
        $aliasRepository->expects($this->once())
            ->method('deleteExpired');

        $messageRepository = $this->mock(MessageRepository::class);
        $messageRepository->expects($this->once())
            ->method('deleteExpired');

        $mailboxRepository = $this->mock(MailboxRepository::class);
        $mailboxRepository->expects($this->once())
            ->method('deleteExpired');

        $otpCodeRepository = $this->mock(OtpCodeRepository::class);
        $otpCodeRepository->expects($this->once())
            ->method('deleteExpired');

        $pairingCodeRepository = $this->mock(PairingCodeRepository::class);
        $pairingCodeRepository->expects($this->once())
            ->method('deleteExpired');

        $attachmentService = $this->mock(AttachmentService::class);
        $attachmentService->expects($this->once())
            ->method('deleteExpired');

        $command = $this->app->find('database:purge');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('purging database', $output);
        $this->assertStringContainsString('purging database done', $output);
    }

    public function testCommandFailure(): void
    {
        $errorMessage = $this->faker->sentence;

        $this->mock(AliasRepository::class);
        $this->mock(MessageRepository::class);
        $this->mock(MailboxRepository::class);
        $this->mock(OtpCodeRepository::class);
        $this->mock(PairingCodeRepository::class);
        $attachmentService = $this->mock(AttachmentService::class);
        $attachmentService->method('deleteExpired')
            ->willThrowException(new AttachmentException($errorMessage));

        $command = $this->app->find('database:purge');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertEquals(1, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(sprintf('ERROR: %s', $errorMessage), $output);
    }
}
