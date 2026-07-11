<?php

use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Resources\AuthUserResource;
use App\Http\Responses\Envelope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Mobile token auth (guest) — cookie SPA auth lives under /auth/session/*.
Route::prefix('v1/auth')->middleware('throttle:mobile-auth')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login']);
    Route::post('/two-factor', [MobileAuthController::class, 'twoFactor']);
});

// idempotency only engages on mutating requests carrying an Idempotency-Key.
Route::middleware(['auth:sanctum', 'idempotency'])->group(function () {
    Route::post('/v1/auth/logout', [MobileAuthController::class, 'logout']);

    Route::get('/v1/ping', fn () => Envelope::success(['status' => 'ok']));

    Route::get('/v1/me', fn (Request $request) => Envelope::success(new AuthUserResource($request->user())));

    Route::get('/v1/devices', [DeviceController::class, 'index']);
    Route::post('/v1/devices', [DeviceController::class, 'store']);
    Route::delete('/v1/devices/{uuid}', [DeviceController::class, 'destroy']);

    Route::post('/v1/sync/operations', [SyncController::class, 'operations']);
    Route::get('/v1/sync/delta', [SyncController::class, 'delta']);

    Route::get('/v1/profile', [ProfileController::class, 'show']);
    Route::patch('/v1/profile', [ProfileController::class, 'update']);
    Route::put('/v1/profile/password', [ProfileController::class, 'updatePassword']);

    Route::middleware('can:users.view')->group(function () {
        Route::get('/v1/users', [UserAdminController::class, 'index']);
        Route::middleware('can:users.create')->post('/v1/users', [UserAdminController::class, 'store']);
        Route::middleware('can:users.update')->patch('/v1/users/{user}', [UserAdminController::class, 'update']);
    });
});
