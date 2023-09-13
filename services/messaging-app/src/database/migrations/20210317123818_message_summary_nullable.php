<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MessageSummaryNullable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message');

        $table->changeColumn('summary', 'string', ['null' => true]);
        $table->addColumn('telephone', 'string', [
            'null' => true,
            'after' => 'to_email',
        ]);

        $table->update();
    }
}
