<?php

use App\Http\Controllers\PayFastController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments/payfast')->group(function () {
    Route::get('/records', [PayFastController::class, 'records']);
    Route::post('/initiate', [PayFastController::class, 'initiateOneTime']);
    Route::post('/subscriptions/initiate', [PayFastController::class, 'initiateSubscription']);
    Route::post('/subscriptions/action', [PayFastController::class, 'subscriptionAction']);
    Route::post('/simulate-itn', [PayFastController::class, 'simulateItn']);
    Route::post('/itn', [PayFastController::class, 'itn'])->withoutMiddleware(['web']);
    Route::get('/return', [PayFastController::class, 'handleReturn']);
    Route::get('/cancel', [PayFastController::class, 'handleCancel']);
});

Route::view('/{any?}', 'application')->where('any', '^(?!api(/|$)).*$');
