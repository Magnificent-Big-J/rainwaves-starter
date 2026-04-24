<?php

use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Resources\AuthUserResource;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/v1/ping', fn () => response()->json(['status' => 'ok']));

    Route::get('/v1/me', fn ($request) => new AuthUserResource($request->user()));

    Route::get('/v1/profile', [ProfileController::class, 'show']);
    Route::patch('/v1/profile', [ProfileController::class, 'update']);
    Route::put('/v1/profile/password', [ProfileController::class, 'updatePassword']);

    Route::middleware('can:users.view')->group(function () {
        Route::get('/v1/users', [UserAdminController::class, 'index']);
        Route::middleware('can:users.create')->post('/v1/users', [UserAdminController::class, 'store']);
        Route::middleware('can:users.update')->patch('/v1/users/{user}', [UserAdminController::class, 'update']);
    });
});
