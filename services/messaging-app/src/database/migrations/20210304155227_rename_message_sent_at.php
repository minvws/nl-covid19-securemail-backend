<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RenameMessageSentAt extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message');

        $table->renameColumn('sent_at', 'notification_sent_at');

        $table->update();
    }
}
