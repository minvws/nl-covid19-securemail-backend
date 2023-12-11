<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Command;

use Carbon\CarbonImmutable;
use Faker\Factory;
use MinVWS\MessagingApp\Repository\AliasRepository;
use MinVWS\MessagingApp\Repository\MailboxRepository;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Repository\OtpCodeRepository;
use MinVWS\MessagingApp\Repository\PairingCodeRepository;
use MinVWS\MessagingApp\Tests\TestHelper\AliasFactory;
use MinVWS\MessagingApp\Tests\TestHelper\MailboxFactory;
use MinVWS\MessagingApp\Tests\TestHelper\MessageFactory;
use MinVWS\MessagingApp\Tests\TestHelper\OtpCodeFactory;
use MinVWS\MessagingApp\Tests\TestHelper\PairingCodeFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function is_string;
use function sprintf;

class TestDataGenerateCommand extends Command
{
    protected static $defaultName = 'test-data:generate';

    public function __construct(
        private readonly AliasRepository $aliasRepository,
        private readonly MailboxRepository $mailboxRepository,
        private readonly MessageRepository $messageRepository,
        private readonly OtpCodeRepository $otpCodeRepository,
        private readonly PairingCodeRepository $pairingCodeRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Generate test-data');
        $this->addOption('email', 'e', InputOption::VALUE_OPTIONAL, 'EmailAddress used for login');
        $this->addOption('pairingCode', 'p', InputOption::VALUE_OPTIONAL, 'PairingCode used for login');
        $this->addOption('otpCode', 'o', InputOption::VALUE_OPTIONAL, 'OtpCode used for login');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $faker = Factory::create('nl_NL');

        $emailAddress = is_string($input->getOption('email')) ? $input->getOption('email') : $faker->safeEmail;
        $pairingCodeCode = is_string($input->getOption('pairingCode'))
            ? $input->getOption('pairingCode')
            : $faker->regexify('[A-Z0-9]{6}');
        $otpCodeCode = is_string($input->getOption('otpCode'))
            ? $input->getOption('otpCode')
            : $faker->numerify('######');

        $mailbox = MailboxFactory::create();
        $this->mailboxRepository->save($mailbox);

        $alias = AliasFactory::create([
            'mailboxUuid' => $mailbox->uuid,
            'emailAddress' => $emailAddress,
        ]);
        $this->aliasRepository->save($alias);

        $message = MessageFactory::generateModel([
            'mailboxUuid' => $mailbox->uuid,
            'aliasUuid' => $alias->uuid,
            'toEmail' => $emailAddress,
            'expiresAt' => null,
            'identityRequired' => false,
        ]);
        $this->messageRepository->save($message);

        $pairingCode = PairingCodeFactory::generateModel([
            'aliasUuid' => $alias->uuid,
            'messageUuid' => $message->uuid,
            'code' => $pairingCodeCode,
            'validUntil' => CarbonImmutable::now()->addYear(),
        ]);
        $this->pairingCodeRepository->save($pairingCode);

        $otpCode = OtpCodeFactory::generateModel([
            'messageUuid' => $message->uuid,
            'type' => 'sms',
            'code' => $otpCodeCode,
            'validUntil' => CarbonImmutable::now()->addYear(),
        ]);
        $this->otpCodeRepository->save($otpCode);

        $output->writeln('Created a message, login with:');
        $output->writeln(sprintf('emailAddress: %s', $emailAddress));
        $output->writeln(sprintf('pairingCode: %s', $pairingCodeCode));
        $output->writeln(sprintf('otpCode: %s', $otpCodeCode));

        return Command::SUCCESS;
    }
}
