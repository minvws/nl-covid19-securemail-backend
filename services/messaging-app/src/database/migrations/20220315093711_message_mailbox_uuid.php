<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MessageMailboxUuid extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message');

        $table->addForeignKey('mailbox_uuid', 'mailbox', 'uuid');

        $table->update();
    }
}
