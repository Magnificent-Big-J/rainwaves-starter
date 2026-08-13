<?php

namespace App\Providers\Modules;

use App\Modules\Billing\Contracts\PayFastCheckoutServiceInterface;
use App\Modules\Billing\Services\PayFastCheckoutService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * RS-301/RS-302: the Billing module — only ever instantiated when
 * MODULE_BILLING_ENABLED evaluates true (see bootstrap/providers.php, the actual
 * on/off switch; this class has no internal enabled-check of its own since it's
 * simply never booted otherwise).
 *
 * Registers everything tied to PayFast/billing: web checkout/ITN/return/cancel
 * routes, the authenticated billing/subscription API routes, local/testing-only
 * PayFast inspection tooling, the subscriptions/payments/payment_events
 * migrations, the payfast-initiate rate limiter, and the checkout service
 * binding — all moved here from AppServiceProvider.
 *
 * RS-302: controllers/models/services/requests/resources now live under
 * App\Modules\Billing\... with their own namespace (see app/Modules/Billing/),
 * not scattered across the normal app/ locations — the fuller physical
 * extraction RS-301 deliberately deferred.
 */
class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PayFastCheckoutServiceInterface::class, PayFastCheckoutService::class);
    }

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
