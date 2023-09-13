<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveMessageIsRead extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message');

        $table->removeColumn('is_read');

        $table->update();
    }
}
