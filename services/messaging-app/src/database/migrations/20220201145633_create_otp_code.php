<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOtpCode extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('otp_code', [
            'id' => false,
            'primary_key' => 'uuid',
        ]);

        $table->addColumn('uuid', 'uuid');
        $table->addColumn('message_uuid', 'uuid');
        $table->addColumn('type', 'string');
        $table->addColumn('code', 'string');
        $table->addColumn('valid_until', 'datetime');
        $table->addTimestampsWithTimezone();

        $table->addForeignKey('message_uuid', 'message', 'uuid');

        $table->addIndex(['message_uuid', 'type', 'code']);

        $table->create();
    }
}
