<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MessageSummary extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message');

        $table->changeColumn('summary', 'text', ['null' => true]);

        $table->update();
    }
}
