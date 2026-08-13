<?php

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\Modules\BillingServiceProvider;
use App\Providers\Modules\MobileServiceProvider;

// This file runs before the container/config exist, so module gating reads env()
// directly rather than config('modules.enabled.*') — config/modules.php reads the
// same env var for everything else (ModuleRegistry, the frontend bootstrap payload)
// that runs after the container is available. Defaults to true: every existing
// install/branch is unaffected unless MODULE_BILLING_ENABLED/MODULE_MOBILE_ENABLED
// is explicitly set.
return array_filter([
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    env('MODULE_BILLING_ENABLED', true) ? BillingServiceProvider::class : null,
    env('MODULE_MOBILE_ENABLED', true) ? MobileServiceProvider::class : null,
]);
