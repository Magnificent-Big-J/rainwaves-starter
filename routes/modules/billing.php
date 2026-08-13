<?php

use App\Http\Controllers\PayFastController;
use Illuminate\Support\Facades\Route;

// Production-safe PayFast surface only: checkout initiation, the ITN webhook, and
// cosmetic return/cancel redirects. None of these can mutate payment/subscription
// state on their own (see PayFastController for the return/cancel note) — that
// authority lives solely with the signed server-to-server ITN call below.
//
// Loaded by App\Providers\Modules\BillingServiceProvider only when the billing
// module is enabled — see config/modules.php / MODULE_BILLING_ENABLED.
Route::prefix('payments/payfast')->group(function () {
    Route::post('/initiate', [PayFastController::class, 'initiateOneTime'])->middleware('throttle:payfast-initiate');
    Route::post('/subscriptions/initiate', [PayFastController::class, 'initiateSubscription'])->middleware('throttle:payfast-initiate');
    Route::post('/itn', [PayFastController::class, 'itn'])->withoutMiddleware(['web']);
    Route::get('/return', [PayFastController::class, 'handleReturn']);
    Route::get('/cancel', [PayFastController::class, 'handleCancel']);
});
