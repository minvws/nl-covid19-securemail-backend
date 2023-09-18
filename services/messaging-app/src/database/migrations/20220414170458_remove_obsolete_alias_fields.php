<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveObsoleteAliasFields extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('alias');

        $table->removeColumn('phonenumber');

        $table->update();
    }
}
