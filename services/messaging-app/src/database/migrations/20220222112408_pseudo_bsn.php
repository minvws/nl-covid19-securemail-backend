<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class PseudoBsn extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('mailbox');

        $table->renameColumn('digid_identifier', 'pseudo_bsn');

        $table->update();
    }
}
