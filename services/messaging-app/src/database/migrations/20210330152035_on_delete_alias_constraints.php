<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class OnDeleteAliasConstraints extends AbstractMigration
{
    public function change(): void
    {
        // message
        $table = $this->table('message');

        $table->dropForeignKey('alias_uuid');
        $table->update();

        $table->changeColumn('alias_uuid', 'string', [
            'null' => true,
        ]);
        $table->addForeignKey('alias_uuid', 'alias', 'uuid', [
            'delete' => 'SET_NULL',
        ]);
        $table->update();

        // pairing_code
        $table = $this->table('pairing_code');

        $table->dropForeignKey('alias_uuid');
        $table->update();

        $table->changeColumn('alias_uuid', 'string', [
            'null' => true,
        ]);
        $table->addForeignKey('alias_uuid', 'alias', 'uuid', [
            'delete' => 'CASCADE',
        ]);
        $table->update();
    }
}
