<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Web\AttachmentController;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    public function boot(Repository $config, FilesystemManager $filesystemManager): void
    {
        $this->app->when(AttachmentController::class)
            ->needs(FilesystemAdapter::class)
            ->give(function () use ($config, $filesystemManager): FilesystemAdapter {
                /** @var FilesystemAdapter $filesystemAdapter */
                $filesystemAdapter = $filesystemManager->disk($config->get('filesystems.attachments'));

                return $filesystemAdapter;
            });
    }
}
