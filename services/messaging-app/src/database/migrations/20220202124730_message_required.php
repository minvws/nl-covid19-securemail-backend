<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MessageRequired extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message');

        $table->addColumn('identity_required', 'boolean');

        $table->update();
    }
}
