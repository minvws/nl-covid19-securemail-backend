<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMessage extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message', [
            'id' => false,
            'primary_key' => 'uuid',
        ]);

        $table->addColumn('uuid', 'uuid');
        $table->addColumn('platform', 'string');
        $table->addColumn('alias_uuid', 'uuid');
        $table->addColumn('mailbox_uuid', 'uuid', ['null' => true]);
        $table->addColumn('from_name', 'string');
        $table->addColumn('from_email', 'string');
        $table->addColumn('to_name', 'string');
        $table->addColumn('to_email', 'string');
        $table->addColumn('subject', 'string');
        $table->addColumn('summary', 'text');
        $table->addColumn('preview', 'text');
        $table->addColumn('text', 'text');
        $table->addColumn('footer', 'text');
        $table->addColumn('sent_at', 'datetime', ['null' => true]);
        $table->addColumn('is_read', 'boolean');
        $table->addTimestampsWithTimezone();

        $table->addForeignKey('alias_uuid', 'alias', 'uuid');

        $table->create();
    }
}
