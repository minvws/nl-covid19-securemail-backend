<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAttachment extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('attachment', [
            'id' => false,
            'primary_key' => 'uuid',
        ]);

        $table->addColumn('uuid', 'uuid');
        $table->addColumn('message_uuid', 'string');
        $table->addColumn('filename', 'string');
        $table->addTimestampsWithTimezone();

        $table->addForeignKey('message_uuid', 'message', 'uuid');

        $table->create();
    }
}
