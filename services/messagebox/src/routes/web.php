<?php

declare(strict_types=1);

use App\Http\Controllers\Web\AttachmentController;
use App\Http\Controllers\Web\Auth\MaxDigidController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\PairingCodeController;
use App\Http\Controllers\Web\PdfController;
use Illuminate\Support\Facades\Route;

# auth
Route::middleware('audit')->group(function () {
    Route::prefix('auth')->name('auth.')->group(static function (): void {
        Route::name('digid.')->group(static function (): void {
            Route::get('digid', [MaxDigidController::class, 'redirectToProvider'])->name('redirect');
            Route::get('callback', [MaxDigidController::class, 'handleProviderCallback'])->name('callback');
        });
    });

    # web
    Route::get('inloggen/code/{code}', [PairingCodeController::class, 'loginByCode'])->name('login.code');
    Route::get('/messages/{uuid}/pdf', [PdfController::class, 'download']);
    Route::get('/messages/{messageUuid}/attachment/{attachmentUuid}/download', [
        AttachmentController::class,
        'download',
    ]);
});


# catch all for the spa
Route::get('/{any?}', [PageController::class, 'page'])->where('any', '.*')->name('page');
