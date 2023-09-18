<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Tests\Feature\Command;

use MinVWS\MessagingApp\Model\Alias;
use MinVWS\MessagingApp\Model\Message;
use MinVWS\MessagingApp\Model\OtpCode;
use MinVWS\MessagingApp\Model\PairingCode;
use MinVWS\MessagingApp\Repository\AliasRepository;
use MinVWS\MessagingApp\Repository\MailboxRepository;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Repository\OtpCodeRepository;
use MinVWS\MessagingApp\Repository\PairingCodeRepository;
use MinVWS\MessagingApp\Tests\Feature\CommandFeatureTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class TestDataGenerateCommandTest extends CommandFeatureTestCase
{
    public function testCommand(): void
    {
        $command = $this->app->find('test-data:generate');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created a message, login with:', $output);
        $this->assertStringContainsString('emailAddress:', $output);
        $this->assertStringContainsString('pairingCode:', $output);
        $this->assertStringContainsString('otpCode:', $output);
    }

    public function testCommandWithParameters(): void
    {
        $emailAddress = $this->faker->safeEmail;
        $pairingCodeCode = $this->faker->regexify('[A-Z0-9]{6}');
        $otpCodeCode = $this->faker->numerify('######');

        $mailboxRepository = $this->mock(MailboxRepository::class);
        $mailboxRepository->expects($this->once())
            ->method('save');

        $aliasRepository = $this->mock(AliasRepository::class);
        $aliasRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Alias $alias) use ($emailAddress): bool {
                return $alias->emailAddress === $emailAddress;
            }));

        $messageRepository = $this->mock(MessageRepository::class);
        $messageRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Message $message) use ($emailAddress): bool {
                return $message->toEmail === $emailAddress;
            }));

        $pairingCodeRepository = $this->mock(PairingCodeRepository::class);
        $pairingCodeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (PairingCode $pairingCode) use ($pairingCodeCode): bool {
                return $pairingCode->code === $pairingCodeCode;
            }));

        $otpCodeRepository = $this->mock(OtpCodeRepository::class);
        $otpCodeRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (OtpCode $otpCode) use ($otpCodeCode): bool {
                return $otpCode->code === $otpCodeCode;
            }));

        $command = $this->app->find('test-data:generate');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--email' => $emailAddress,
            '--pairingCode' => $pairingCodeCode,
            '--otpCode' => $otpCodeCode,
        ]);
    }
}
