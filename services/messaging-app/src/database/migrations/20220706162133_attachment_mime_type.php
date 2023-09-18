<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AttachmentMimeType extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('attachment');

        $table->addColumn('mime_type', 'string', [
            'after' => 'filename',
        ]);

        $table->update();
    }
}
