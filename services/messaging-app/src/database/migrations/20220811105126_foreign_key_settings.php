<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ForeignKeySettings extends AbstractMigration
{
    public function change(): void
    {
        $aliasTable = $this->table('alias');
        $aliasTable->dropForeignKey('mailbox_uuid');
        $aliasTable->addForeignKey('mailbox_uuid', 'mailbox', 'uuid', [
            'delete' => 'SET_NULL',
        ]);
        $aliasTable->update();

        $attachmentTable = $this->table('attachment');
        $attachmentTable->dropForeignKey('message_uuid');
        $attachmentTable->update();
        $attachmentTable->changeColumn('message_uuid', 'string', [
            'null' => true,
        ]);
        $attachmentTable->addForeignKey('message_uuid', 'message', 'uuid', [
            'delete' => 'SET_NULL',
        ]);
        $attachmentTable->update();

        $messageTable = $this->table('message');
        $messageTable->dropForeignKey('mailbox_uuid');
        $messageTable->addForeignKey('mailbox_uuid', 'mailbox', 'uuid', [
            'delete' => 'SET_NULL',
        ]);
        $messageTable->update();

        $otpCodeTable = $this->table('otp_code');
        $otpCodeTable->dropForeignKey('message_uuid');
        $otpCodeTable->update();
        $otpCodeTable->changeColumn('message_uuid', 'string', [
            'null' => true,
        ]);
        $otpCodeTable->addForeignKey('message_uuid', 'message', 'uuid', [
            'delete' => 'SET_NULL',
        ]);
        $otpCodeTable->update();

        $pairingCodeTable = $this->table('pairing_code');
        $pairingCodeTable->dropForeignKey('alias_uuid');
        $pairingCodeTable->dropForeignKey('message_uuid');
        $pairingCodeTable->update();
        $pairingCodeTable->changeColumn('message_uuid', 'string', [
            'null' => true,
        ]);
        $pairingCodeTable->addForeignKey('alias_uuid', 'alias', 'uuid', [
            'delete' => 'SET NULL',
        ]);
        $pairingCodeTable->addForeignKey('message_uuid', 'message', 'uuid', [
            'delete' => 'SET NULL',
        ]);
        $pairingCodeTable->update();
    }
}
