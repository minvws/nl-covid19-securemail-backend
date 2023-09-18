<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveObsoleteMessageFields extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message');

        $table->removeColumn('preview');
        $table->removeColumn('summary');

        $table->update();
    }
}
