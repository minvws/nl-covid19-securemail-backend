<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MessageStatusFields extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message');

        $table->addColumn('received_at', 'datetime', ['null' => true]);
        $table->addColumn('bounced_at', 'datetime', ['null' => true]);
        $table->addColumn('otp_auth_failed_at', 'datetime', ['null' => true]);
        $table->addColumn('digid_auth_failed_at', 'datetime', ['null' => true]);
        $table->addColumn('first_read_at', 'datetime', ['null' => true]);
        $table->addColumn('revoked_at', 'datetime', ['null' => true]);
        $table->addColumn('expired_at', 'datetime', ['null' => true]);

        $table->update();
    }
}
