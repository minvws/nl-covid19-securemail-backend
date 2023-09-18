<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Command;

use Exception;
use Laminas\Config\Config;
use MinVWS\MessagingApp\Queue\QueueWorker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueWorkCommand extends Command
{
    protected static $defaultName = 'queue:work';

    public function __construct(
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        private readonly QueueWorker $queueWorker,
    ) {
        parent::__construct();

        $this->setDescription('Run queue worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $this->config->get('queue')->get('task_limit_per_run');
        $this->logger->debug('running queue worker');

        try {
            $this->queueWorker->process($limit);

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $this->logger->debug('exception occurred', ['exception' => $exception]);

            return Command::FAILURE;
        }
    }
}
