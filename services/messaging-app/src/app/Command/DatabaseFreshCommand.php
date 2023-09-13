<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Command;

use Illuminate\Database\ConnectionInterface;
use Phinx\Console\PhinxApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function implode;
use function reset;
use function sprintf;

class DatabaseFreshCommand extends Command
{
    protected static $defaultName = 'database:fresh';

    public function __construct(
        private readonly ConnectionInterface $databaseConnection,
    ) {
        parent::__construct();

        $this->setDescription('Drop all database tables, run migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $baseTables = $this->databaseConnection->select("SHOW FULL TABLES WHERE table_type = 'BASE TABLE'");
        $tableNames = [];
        foreach ($baseTables as $baseTable) {
            $tableNames[] = @reset($baseTable);
        }

        $this->databaseConnection->statement('SET FOREIGN_KEY_CHECKS=0;');
        $this->databaseConnection->statement(sprintf('drop table %s', implode(',', $tableNames)));
        $this->databaseConnection->statement('SET FOREIGN_KEY_CHECKS=1;');

        $app = new PhinxApplication();
        $app->setAutoExit(false);

        $output = new ConsoleOutput();
        return $app->run(new StringInput('migrate'), $output);
    }
}
