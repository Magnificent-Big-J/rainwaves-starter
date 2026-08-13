<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

// Loaded by App\Providers\Modules\MobileServiceProvider only when the mobile module
// is enabled — see config/modules.php / MODULE_MOBILE_ENABLED. Re-declares the same
// auth:sanctum + idempotency wrapper routes/api.php uses, since loadRoutesFrom()
// doesn't inherit an outer group from a different file.

// Public mobile bootstrap.
Route::get('/v1/meta', [MetaController::class, 'show'])->middleware('throttle:60,1');

// Mobile token auth (guest) — cookie SPA auth lives under /auth/session/*.
Route::prefix('v1/auth')->middleware('throttle:mobile-auth')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login']);
    Route::post('/two-factor', [MobileAuthController::class, 'twoFactor']);
});

Route::middleware(['auth:sanctum', 'idempotency'])->group(function () {
    Route::post('/v1/auth/logout', [MobileAuthController::class, 'logout']);

    Route::get('/v1/devices', [DeviceController::class, 'index']);
    Route::post('/v1/devices', [DeviceController::class, 'store']);
    Route::delete('/v1/devices/{uuid}', [DeviceController::class, 'destroy']);

    Route::post('/v1/sync/operations', [SyncController::class, 'operations']);
    Route::get('/v1/sync/delta', [SyncController::class, 'delta']);
});
