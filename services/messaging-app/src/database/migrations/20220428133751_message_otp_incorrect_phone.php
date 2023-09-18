<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MessageOtpIncorrectPhone extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('message');

        $table->addColumn('otp_incorrect_phone_at', 'datetime', [
            'null' => true,
            'after' => 'otp_auth_failed_at',
        ]);

        $table->update();
    }
}
