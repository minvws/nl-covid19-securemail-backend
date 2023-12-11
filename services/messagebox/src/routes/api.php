<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\OtpCodeController;
use App\Http\Controllers\Api\PairingCodeController;
use App\Http\Controllers\Api\StatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('/v1')->middleware('api')->group(static function (): void {
    Route::controller(StatusController::class)->group(static function (): void {
        Route::get('/ping', 'ping');
        Route::get('/status', 'status');
    });

    Route::prefix('/auth')->controller(AuthController::class)->group(static function (): void {
        Route::get('/options', 'getOptions')->name('options');

        Route::get('/keep-alive', 'keepAlive');
        Route::get('/logout', 'logout')->middleware('audit');
    });

    Route::middleware('audit')->group(function () {
        Route::prefix('messages')->controller(MessageController::class)->group(static function (): void {
            Route::get('/', 'getList')->name('list');
            Route::get('/{uuid}', 'getByUuid')->name('uuid');
            Route::post('/unlink', 'unlinkByUuid')->name('unlink');
        });

        Route::prefix('/otp')->controller(OtpCodeController::class)->group(static function (): void {
            Route::post('/', 'postOtpCode');
            Route::post('/incorrect-phone', 'postIncorrectPhone');
            Route::post('/info', 'getInfo');
            Route::post('/request', 'requestOtpCode');
        });

        Route::prefix('/pairing_code')->controller(PairingCodeController::class)->group(static function (): void {
            Route::post('', 'postPairingCode');
            Route::post('/renew', 'postPairingCodeRenew');
        });
    });
});
