<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Security extends AbstractMigration
{
    public function change(): void
    {
        // alias
        $aliasTable = $this->table('alias');

        $aliasTable->removeIndex('email_address');
        $aliasTable->update();

        $aliasTable->changeColumn('email_address', 'text');
        $aliasTable->update();


        $mailboxTable = $this->table('mailbox');

        $mailboxTable->removeIndex('pseudo_bsn');
        $mailboxTable->update();

        $mailboxTable->addIndex('pseudo_bsn');
        $mailboxTable->update();

        // message
        $messageTable = $this->table('message');

        $messageTable->changeColumn('from_name', 'text');
        $messageTable->changeColumn('from_email', 'text');
        $messageTable->changeColumn('to_name', 'text');
        $messageTable->changeColumn('to_email', 'text');
        $messageTable->changeColumn('phone_number', 'text', ['null' => true]);
        $messageTable->changeColumn('subject', 'text');

        $messageTable->addColumn('to_email_hash', 'string', [
            'null' => true,
            'after' => 'footer',
        ]);

        $messageTable->addIndex('to_email_hash');

        $messageTable->update();
    }
}
