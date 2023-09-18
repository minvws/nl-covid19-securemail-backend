<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAlias extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('alias', [
            'id' => false,
            'primary_key' => 'uuid',
        ]);

        $table->addColumn('uuid', 'uuid');
        $table->addColumn('mailbox_uuid', 'string', ['null' => true]);
        $table->addColumn('platform', 'string');
        $table->addColumn('platform_identifier', 'string');
        $table->addColumn('email_address', 'string');
        $table->addColumn('phonenumber', 'string', ['null' => true]);
        $table->addTimestampsWithTimezone();

        $table->addForeignKey('mailbox_uuid', 'mailbox', 'uuid');

        $table->addIndex(['platform', 'platform_identifier'], ['unique' => true]);
        $table->addIndex(['email_address']);

        $table->create();
    }
}
