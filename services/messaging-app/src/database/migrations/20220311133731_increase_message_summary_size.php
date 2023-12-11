<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class IncreaseMessageSummarySize extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message');

        $table->changeColumn('summary', 'text');

        $table->update();
    }
}
