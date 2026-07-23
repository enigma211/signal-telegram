<?php

use App\Http\Controllers\Api\PythonSignalController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Telegram Webhooks (FA / EN bots)
|--------------------------------------------------------------------------
*/
Route::post('/telegram/webhook/{language}', TelegramWebhookController::class)
    ->middleware('telegram.webhook')
    ->whereIn('language', ['fa', 'en'])
    ->name('telegram.webhook');

/*
|--------------------------------------------------------------------------
| Secure Python AI Signal API
|--------------------------------------------------------------------------
*/
Route::middleware('api.token')->group(function () {
    Route::post('/signals', [PythonSignalController::class, 'store'])
        ->name('api.signals.store');

    Route::post('/signals/update', [PythonSignalController::class, 'update'])
        ->name('api.signals.update');

    Route::post('/signals/result', [PythonSignalController::class, 'result'])
        ->name('api.signals.result');
});
