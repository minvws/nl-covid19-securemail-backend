<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AliasExpiresAt extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('alias');

        $table->addColumn('expires_at', 'datetime', [
            'null' => true,
            'after' => 'platform_identifier',
        ]);

        $table->update();
    }
}
