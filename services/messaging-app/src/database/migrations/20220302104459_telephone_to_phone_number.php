<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class TelephoneToPhoneNumber extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message');

        $table->renameColumn('telephone', 'phone_number');

        $table->update();
    }
}
