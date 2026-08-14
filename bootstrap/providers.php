<?php

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\Modules\BillingServiceProvider;
use App\Providers\Modules\GovernanceServiceProvider;
use App\Providers\Modules\MobileServiceProvider;
use App\Providers\Modules\TeamsServiceProvider;

// This file runs before the container/config exist, so module gating reads env()
// directly rather than config('modules.enabled.*') — config/modules.php reads the
// same env var for everything else (ModuleRegistry, the frontend bootstrap payload)
// that runs after the container is available. Defaults to true: every existing
// install/branch is unaffected unless MODULE_BILLING_ENABLED/MODULE_MOBILE_ENABLED/
// MODULE_TEAMS_ENABLED is explicitly set.
//
// ModuleRegistry (config/modules.php) validates the same dependency, but only when
// something actually resolves it from the container — a plain `route:list` or an
// unrelated request never touches it, so Teams' routes/migrations would otherwise
// register regardless of Billing's state, only surfacing as an uncaught exception
// wherever ModuleRegistry does eventually get resolved. Checked here too so an
// invalid combination fails loudly at boot, before anything registers, not lazily
// and inconsistently depending on what a given request happens to touch.
if (env('MODULE_TEAMS_ENABLED', true) && ! env('MODULE_BILLING_ENABLED', true)) {
    throw new RuntimeException('MODULE_TEAMS_ENABLED requires MODULE_BILLING_ENABLED — the Teams module depends on Billing.');
}

return array_filter([
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    env('MODULE_BILLING_ENABLED', true) ? BillingServiceProvider::class : null,
    env('MODULE_MOBILE_ENABLED', true) ? MobileServiceProvider::class : null,
    env('MODULE_TEAMS_ENABLED', true) ? TeamsServiceProvider::class : null,
    env('MODULE_GOVERNANCE_ENABLED', true) ? GovernanceServiceProvider::class : null,
]);
