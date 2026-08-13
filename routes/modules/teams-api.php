<?php

use App\Modules\Teams\Http\Controllers\Admin\AdminTeamController;
use App\Modules\Teams\Http\Controllers\TeamController;
use App\Modules\Teams\Http\Controllers\TeamInviteController;
use App\Modules\Teams\Http\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;

// Loaded by App\Providers\Modules\TeamsServiceProvider only when the teams module is
// enabled — see config/modules.php / MODULE_TEAMS_ENABLED. Re-declares the same
// auth:sanctum + idempotency wrapper routes/api.php uses, since loadRoutesFrom()
// doesn't inherit an outer group from a different file.
Route::middleware(['auth:sanctum', 'idempotency'])->group(function () {
    Route::get('/v1/team', [TeamController::class, 'show']);
    Route::post('/v1/teams', [TeamController::class, 'store']);
    Route::patch('/v1/teams/{team}', [TeamController::class, 'update']);
    Route::post('/v1/teams/{team}/switch', [TeamController::class, 'switch']);
    Route::delete('/v1/teams/{team}', [TeamController::class, 'destroy']);

    Route::get('/v1/teams/{team}/members', [TeamMemberController::class, 'index']);
    Route::patch('/v1/teams/{team}/members/{user}', [TeamMemberController::class, 'update']);
    Route::delete('/v1/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy']);

    Route::get('/v1/teams/{team}/invites', [TeamInviteController::class, 'index']);
    Route::post('/v1/teams/{team}/invites', [TeamInviteController::class, 'store']);
    Route::delete('/v1/teams/{team}/invites/{invite}', [TeamInviteController::class, 'destroy']);
    Route::post('/v1/team-invites/{token}/accept', [TeamInviteController::class, 'accept']);

    Route::middleware('can:teams.view')->group(function () {
        Route::get('/v1/admin/teams', [AdminTeamController::class, 'index']);
        Route::get('/v1/admin/teams/{team}', [AdminTeamController::class, 'show']);
    });
});
