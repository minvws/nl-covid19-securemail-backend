<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MessageExpiresAt extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message');

        $table->addColumn('expires_at', 'datetime', [
            'null' => true,
            'after' => 'footer',
        ]);

        $table->update();
    }
}
