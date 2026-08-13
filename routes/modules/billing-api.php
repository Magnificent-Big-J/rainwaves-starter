<?php

use App\Modules\Billing\Http\Controllers\BillingController;
use App\Modules\Billing\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Loaded by App\Providers\Modules\BillingServiceProvider only when the billing module
// is enabled — see config/modules.php / MODULE_BILLING_ENABLED. Re-declares the same
// auth:sanctum + idempotency wrapper routes/api.php uses, since loadRoutesFrom()
// doesn't inherit an outer group from a different file.
Route::middleware(['auth:sanctum', 'idempotency'])->group(function () {
    Route::get('/v1/billing', [BillingController::class, 'show']);
    Route::get('/v1/billing/plans', [BillingController::class, 'plans']);
    Route::get('/v1/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('/v1/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);
});
