<?php

use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\RoleAdminController;
use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\WebConfigController;
use App\Http\Resources\AuthUserResource;
use App\Http\Responses\Envelope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public mobile bootstrap.
Route::get('/v1/meta', [MetaController::class, 'show'])->middleware('throttle:60,1');

// Public SPA bootstrap: brand + navigation + feature flags (RS-101/RS-102).
Route::get('/v1/web-config', [WebConfigController::class, 'show'])->middleware('throttle:60,1');

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

    Route::get('/v1/notifications', [NotificationController::class, 'index']);
    Route::post('/v1/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/v1/notifications/{id}/read', [NotificationController::class, 'markRead']);

    Route::get('/v1/sessions', [SessionController::class, 'index']);
    Route::delete('/v1/sessions/others', [SessionController::class, 'destroyOthers']);
    Route::delete('/v1/sessions/{id}', [SessionController::class, 'destroy']);

    Route::get('/v1/billing', [BillingController::class, 'show']);
    Route::get('/v1/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('/v1/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel']);

    Route::get('/v1/profile', [ProfileController::class, 'show']);
    Route::patch('/v1/profile', [ProfileController::class, 'update']);
    Route::put('/v1/profile/password', [ProfileController::class, 'updatePassword']);

    Route::middleware('can:users.view')->group(function () {
        Route::get('/v1/users', [UserAdminController::class, 'index']);
        Route::get('/v1/users/export', [UserAdminController::class, 'export']);
        Route::middleware('can:users.create')->post('/v1/users', [UserAdminController::class, 'store']);
        Route::middleware('can:users.update')->patch('/v1/users/{user}', [UserAdminController::class, 'update']);
        Route::middleware('can:users.delete')->delete('/v1/users/{user}', [UserAdminController::class, 'destroy']);
        Route::middleware('can:users.delete')->post('/v1/users/{user}/restore', [UserAdminController::class, 'restore'])->withTrashed();
    });

    Route::middleware('can:roles.view')->group(function () {
        Route::get('/v1/roles', [RoleAdminController::class, 'index']);
        Route::middleware('can:roles.manage')->put('/v1/roles/{role}/permissions', [RoleAdminController::class, 'updatePermissions']);
    });

    Route::middleware('can:activity.view')->group(function () {
        Route::get('/v1/activity-log', [ActivityLogController::class, 'index']);
        Route::get('/v1/activity-log/export', [ActivityLogController::class, 'export']);
    });
});
