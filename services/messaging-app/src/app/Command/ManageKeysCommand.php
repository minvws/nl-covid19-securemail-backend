<?php

namespace MinVWS\MessagingApp\Command;

use Closure;
use Exception;
use MinVWS\DBCO\Encryption\Security\StorageTerm;
use MinVWS\DBCO\Encryption\Security\StorageTermUnit;
use MinVWS\MessagingApp\Service\SecurityService;
use Predis\PredisException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function is_bool;
use function is_string;
use function sleep;
use function sprintf;
use function strtoupper;

/**
 * Continuous process that has the following responsibilities:
 * - Make sure all necessary keys exist in the HSM.
 * - Make sure all necessary keys exist in the cache.
 */
class ManageKeysCommand extends Command
{
    protected static $defaultName = 'security:manage-keys';

    public function __construct(
        private readonly SecurityService $securityService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Continuous manage security module keys')
            ->setHelp('Continuous process for creating, caching and rotating security module keys')
            ->addOption(
                'createMissingPastKeys',
                'p',
                InputOption::VALUE_NONE,
                'Create keys that are in the past, but are missing, useful for environments that generate test data'
            )
            ->addOption(
                'singleRun',
                's',
                InputOption::VALUE_NONE,
                'Single run, only create/load keys once and quit immediately afterwards'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $createMissingPastKeys = (bool) $input->getOption('createMissingPastKeys');
        $singleRun = $input->getOption('singleRun');
        $currentTermUnit = null;

        while (true) {
            try {
                $currentTermUnit = $this->manageStoreSecretKeys(
                    $output,
                    StorageTerm::short(),
                    $currentTermUnit,
                    $createMissingPastKeys
                );

                if (!$this->invoke('Cache keys in memory', $output, fn() => $this->securityService->cacheKeys(false))) {
                    return Command::FAILURE;
                }

                if ($singleRun) {
                    return Command::SUCCESS;
                }

                $createMissingPastKeys = false;

                sleep(60);
            } catch (Exception) {
                // fatal error, exit so the auto-restart kicks in
                return Command::FAILURE;
            }
        }
    }

    private function invoke(string $label, OutputInterface $output, Closure $closure): bool|string
    {
        $output->write(sprintf('%s... ', $label));

        try {
            $result = $closure();

            if (is_bool($result)) {
                $result = $result ? 'OK' : 'FAILED';
            } elseif (!is_string($result)) {
                $result = 'OK';
            }

            $output->writeln(sprintf('[%s]', strtoupper($result)));
            return $result;
        } catch (Exception $e) {
            $output->writeln('[ERROR]');
            $output->writeln(sprintf("ERROR: %s", $e->getMessage()));
            return false;
        }
    }

    /**
     * Manage store secret keys for the given storage term.
     *
     * @throws PredisException
     */
    private function manageStoreSecretKeys(
        OutputInterface $output,
        StorageTerm $term,
        ?StorageTermUnit $previousCurrentUnit,
        bool $createMissingPastKeys = false,
    ): StorageTermUnit {
        try {
            return $this->securityService->manageStoreSecretKeys(
                $term,
                $previousCurrentUnit,
                function (StorageTermUnit $unit, string $mutation, ?Exception $exception = null) use ($output, $term) {
                    $output->writeln(
                        sprintf(
                            'Manage %s term storage key "%s"... [%s]',
                            $term,
                            $unit,
                            strtoupper($mutation)
                        )
                    );

                    if ($exception !== null) {
                        $output->writeln(sprintf('ERROR: %s', $exception->getMessage()));
                        if ($exception instanceof PredisException) {
                            // fatal error
                            throw $exception;
                        }
                    }
                },
                $createMissingPastKeys
            );
        } catch (Exception $exception) {
            $output->writeln(sprintf('FATAL ERROR: %s', $exception->getMessage()));
            throw $exception;
        }
    }
}
