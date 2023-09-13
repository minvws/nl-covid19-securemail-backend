<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ExpandStatusUpdates extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('pairing_code');

        $table->addColumn('paired_at', 'datetime', [
            'null' => true,
            'after' => 'valid_until',
        ]);

        $table->update();
    }
}
