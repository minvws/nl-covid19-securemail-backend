<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMailbox extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('mailbox', [
            'id' => false,
            'primary_key' => 'uuid',
        ]);

        $table->addColumn('uuid', 'uuid');
        $table->addColumn('digid_identifier', 'string', [
            'null' => true,
        ]);
        $table->addTimestampsWithTimezone();

        $table->addIndex(['digid_identifier']);

        $table->create();
    }
}
