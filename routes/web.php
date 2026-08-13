<?php

use App\Http\Controllers\PayFastController;
use Illuminate\Support\Facades\Route;

// Production-safe PayFast surface only: checkout initiation, the ITN webhook, and
// cosmetic return/cancel redirects. None of these can mutate payment/subscription
// state on their own (see PayFastController for the return/cancel note) — that
// authority lives solely with the signed server-to-server ITN call below.
Route::prefix('payments/payfast')->group(function () {
    Route::post('/initiate', [PayFastController::class, 'initiateOneTime'])->middleware('throttle:payfast-initiate');
    Route::post('/subscriptions/initiate', [PayFastController::class, 'initiateSubscription'])->middleware('throttle:payfast-initiate');
    Route::post('/itn', [PayFastController::class, 'itn'])->withoutMiddleware(['web']);
    Route::get('/return', [PayFastController::class, 'handleReturn']);
    Route::get('/cancel', [PayFastController::class, 'handleCancel']);
});

// Local/QA-only PayFast inspection & simulation routes (routes/payfast-local.php),
// registered here — before the SPA catch-all below — so they only ever exist in the
// route table when app()->environment() is local/testing (see RS-003 test coverage
// in tests/Feature/ProductionRouteHardeningTest.php).
if (app()->environment(['local', 'testing'])) {
    require __DIR__.'/payfast-local.php';
}

Route::view('/{any?}', 'application')->where('any', '^(?!api(/|$)).*$');
