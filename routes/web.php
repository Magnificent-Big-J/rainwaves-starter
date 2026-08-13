<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

// Readiness probe — see HealthController for how this differs from Laravel's built-in
// /up (liveness only). Registered before the SPA catch-all, same as everything else here.
Route::get('/health', [HealthController::class, 'index']);

// PayFast/billing routes (production checkout/ITN/return/cancel, plus the
// local/testing-only inspection & simulation tooling) live in
// routes/modules/billing.php and routes/payfast-local.php, loaded by
// App\Providers\Modules\BillingServiceProvider — see RS-301/config/modules.php.

Route::view('/{any?}', 'application')->where('any', '^(?!api(/|$)).*$');
