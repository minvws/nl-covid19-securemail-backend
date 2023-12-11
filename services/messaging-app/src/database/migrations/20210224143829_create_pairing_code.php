<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePairingCode extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('pairing_code', [
            'id' => false,
            'primary_key' => 'uuid',
        ]);

        $table->addColumn('uuid', 'uuid');
        $table->addColumn('alias_uuid', 'uuid');
        $table->addColumn('message_uuid', 'uuid');
        $table->addColumn('code', 'string');
        $table->addColumn('previous_code', 'string', ['null' => true]);
        $table->addColumn('valid_until', 'datetime');
        $table->addTimestampsWithTimezone();

        $table->addForeignKey('alias_uuid', 'alias', 'uuid', ['delete' => 'CASCADE']);
        $table->addForeignKey('message_uuid', 'message', 'uuid', ['delete' => 'CASCADE']);

        $table->addIndex(['message_uuid'], ['unique' => true]);
        $table->addIndex(['code']);

        $table->create();
    }
}
