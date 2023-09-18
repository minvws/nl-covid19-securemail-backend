<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MessageAttachmentsEncryptionKey extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message');

        $table->addColumn('attachments_encryption_key', 'text', [
            'after' => 'to_email_hash',
        ]);

        $table->update();
    }
}
