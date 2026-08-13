<?php

use App\Http\Controllers\PayFastController;
use Illuminate\Support\Facades\Route;

// Dev/QA-only PayFast inspection and simulation tooling. This file is only ever
// loaded when the app is booted in the local/testing environment (see
// bootstrap/app.php) — it must never be reachable in staging or production.
// Controller actions additionally self-check the environment as defense in depth.
Route::prefix('payments/payfast')->group(function () {
    Route::get('/records', [PayFastController::class, 'records']);
    Route::post('/simulate-itn', [PayFastController::class, 'simulateItn']);
    Route::post('/subscriptions/action', [PayFastController::class, 'subscriptionAction']);
});
