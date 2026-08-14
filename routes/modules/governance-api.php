<?php

use App\Modules\Governance\Http\Controllers\Admin\RoleChangeRequestController;
use App\Modules\Governance\Http\Controllers\GovernanceController;
use App\Modules\Governance\Http\Controllers\LegalController;
use Illuminate\Support\Facades\Route;

// Loaded by App\Providers\Modules\GovernanceServiceProvider only when the governance
// module is enabled — see config/modules.php / MODULE_GOVERNANCE_ENABLED. Re-declares
// the same auth:sanctum + idempotency wrapper routes/api.php uses, since
// loadRoutesFrom() doesn't inherit an outer group from a different file.
Route::middleware(['auth:sanctum', 'idempotency'])->group(function () {
    Route::get('/v1/legal/status', [LegalController::class, 'status']);
    Route::post('/v1/legal/accept', [LegalController::class, 'accept']);

    Route::get('/v1/governance/export', [GovernanceController::class, 'exportData']);
    Route::delete('/v1/governance/account', [GovernanceController::class, 'deleteAccount']);

    Route::middleware('can:governance.manage')->group(function () {
        Route::get('/v1/governance/role-change-requests', [RoleChangeRequestController::class, 'index']);
        Route::post('/v1/governance/role-change-requests/{roleChangeRequest}/approve', [RoleChangeRequestController::class, 'approve']);
        Route::post('/v1/governance/role-change-requests/{roleChangeRequest}/reject', [RoleChangeRequestController::class, 'reject']);
    });
});
