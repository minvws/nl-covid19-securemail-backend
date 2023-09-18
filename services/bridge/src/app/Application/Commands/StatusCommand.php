<?php

declare(strict_types=1);

namespace App\Application\Commands;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use Predis\Client as PredisClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

class StatusCommand extends Command
{
    protected static $defaultName = 'status';

    public function __construct(
        private readonly PredisClient $redisClient,
        private readonly GuzzleClient $messagingAppGuzzleClient,
    ) {
        parent::__construct();

        $this->setDescription('Check status of dependencies');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $redisOK = $this->checkStatus('Redis', $output, fn () => $this->redisClient->ping() === 'PONG');
        $messagingApp = $this->checkStatus('Messaging App', $output, function () {
            $options = [
                'connect_timeout' => 5,
                'read_timeout' => 5,
                'timeout' => 15,
            ];
            $response = $this->messagingAppGuzzleClient->get('api/v1/ping', $options);
            return $response->getStatusCode() === 200 && (string)$response->getBody() === 'PONG';
        });

        return $redisOK && $messagingApp ? Command::SUCCESS : Command::FAILURE;
    }

    private function checkStatus(string $label, OutputInterface $output, callable $callback): bool
    {
        $result = false;

        $output->write(sprintf('Checking %s status...', $label));

        try {
            $result = $callback();
        } catch (Exception) {
            // do nothing
        }

        $output->writeln(sprintf(' [ %s ]', ($result ? 'OK' : 'ERROR')));

        return $result;
    }
}
