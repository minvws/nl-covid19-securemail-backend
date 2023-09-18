<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Command;

use MinVWS\MessagingApp\Repository\AliasRepository;
use MinVWS\MessagingApp\Repository\MailboxRepository;
use MinVWS\MessagingApp\Repository\MessageRepository;
use MinVWS\MessagingApp\Repository\OtpCodeRepository;
use MinVWS\MessagingApp\Repository\PairingCodeRepository;
use MinVWS\MessagingApp\Service\AttachmentException;
use MinVWS\MessagingApp\Service\AttachmentService;
use Psr\Log\LoggerInterface;
use SecureMail\Shared\Application\Exceptions\RepositoryException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

class DatabasePurgeCommand extends Command
{
    protected static $defaultName = 'database:purge';

    public function __construct(
        private readonly AliasRepository $aliasRepository,
        private readonly AttachmentService $attachmentService,
        private readonly LoggerInterface $logger,
        private readonly MailboxRepository $mailboxRepository,
        private readonly MessageRepository $messageRepository,
        private readonly OtpCodeRepository $otpCodeRepository,
        private readonly PairingCodeRepository $pairingCodeRepository,
    ) {
        parent::__construct();

        $this->setDescription('Purge expired database entries');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('purging database');

        try {
            $output->writeln('alias delete expired');
            $this->aliasRepository->deleteExpired();

            $output->writeln('message delete expired');
            $this->messageRepository->deleteExpired();

            $output->writeln('mailbox delete expired');
            $this->mailboxRepository->deleteExpired();

            $output->writeln('otpCode delete expired');
            $this->otpCodeRepository->deleteExpired();

            $output->writeln('pairingCode delete expired');
            $this->pairingCodeRepository->deleteExpired();

            $output->writeln('attachment delete expired');
            $this->attachmentService->deleteExpired();
        } catch (AttachmentException | RepositoryException $exception) {
            $this->logger->error('error purging database', ['exception' => $exception]);
            $output->writeln(sprintf('ERROR: %s', $exception->getMessage()));

            return Command::FAILURE;
        }

        $output->writeln('purging database done');

        return Command::SUCCESS;
    }
}
