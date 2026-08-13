<?php

namespace App\Providers\Modules;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * RS-301: the Billing module — only ever instantiated when MODULE_BILLING_ENABLED
 * evaluates true (see bootstrap/providers.php, the actual on/off switch; this class
 * has no internal enabled-check of its own since it's simply never booted otherwise).
 *
 * Registers everything the earlier catalog found tied to PayFast/billing at the
 * routing/migration/rate-limiter level: web checkout/ITN/return/cancel routes, the
 * authenticated billing/subscription API routes, local/testing-only PayFast
 * inspection tooling, the subscriptions/payments/payment_events migrations, and the
 * payfast-initiate rate limiter (moved here from AppServiceProvider).
 *
 * Deliberately does NOT relocate controllers/models/services/resources — those stay
 * in their existing app/ locations. Only routes and migrations need to move, since
 * those are the two things Laravel discovers by directory convention rather than PSR-4
 * autoloading. Full physical module extraction is separate, larger scope (RS-302).
 */
class BillingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/modules/billing.php'));

        // routes/api.php's routes get the `api` prefix + middleware group automatically
        // via bootstrap/app.php's withRouting(api: ...) — that automatic wrapping only
        // applies to the one file passed there, so a module route file loaded via
        // loadRoutesFrom() from an arbitrary provider has to declare it explicitly to
        // end up with the same api/v1/... URI and api middleware stack.
        Route::prefix('api')->middleware('api')->group(function () {
            $this->loadRoutesFrom(base_path('routes/modules/billing-api.php'));
        });

        if ($this->app->environment(['local', 'testing'])) {
            $this->loadRoutesFrom(base_path('routes/payfast-local.php'));
        }

        $this->loadMigrationsFrom(database_path('migrations/modules/billing'));

        RateLimiter::for('payfast-initiate', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perMinute(5)->by('payfast-initiate|'.($request->user()?->id ?? $request->ip())),
            ];
        });
    }
}
