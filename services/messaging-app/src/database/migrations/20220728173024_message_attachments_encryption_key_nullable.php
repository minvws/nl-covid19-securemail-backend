<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MessageAttachmentsEncryptionKeyNullable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message');

        $table->changeColumn('attachments_encryption_key', 'text', [
            'after' => 'to_email_hash',
            'null' => true,
        ]);

        $table->update();

        $this->execute('UPDATE `message` SET `attachments_encryption_key` = NULL WHERE `attachments_encryption_key` = ""');
    }
}
