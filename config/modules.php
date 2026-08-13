<?php

use App\Modules\Billing\BillingModule;
use App\Modules\Mobile\MobileModule;

/*
|--------------------------------------------------------------------------
| Module registry
|--------------------------------------------------------------------------
|
| Registering a manifest class in `modules` does NOT enable it — see `enabled`
| below. `bootstrap/providers.php` reads the same MODULE_*_ENABLED env vars
| directly (it runs before this config file is loaded) to decide whether a
| module's own ServiceProvider is instantiated at all — that's the real on/off
| switch. This file is what the rest of the app (ModuleRegistry, the frontend
| bootstrap payload) reads at runtime to ask "is X enabled?".
|
*/

return [
    'modules' => [
        BillingModule::class,
        MobileModule::class,
    ],

    'enabled' => [
        'billing' => env('MODULE_BILLING_ENABLED', true),
        'mobile' => env('MODULE_MOBILE_ENABLED', true),
    ],
];
