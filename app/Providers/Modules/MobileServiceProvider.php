<?php

namespace App\Providers\Modules;

use App\Contracts\MobileAuthServiceInterface;
use App\Contracts\Sync\SyncProcessorInterface;
use App\Services\MobileAuthService;
use App\Services\Sync\SyncProcessor;
use App\Services\Sync\SyncRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The Mobile module — the native/companion mobile app's API surface: bootstrap
 * (/v1/meta), token auth, device registration and offline sync. Only ever
 * instantiated when MODULE_MOBILE_ENABLED evaluates true (see
 * bootstrap/providers.php, the actual on/off switch; this class has no internal
 * enabled-check of its own since it's simply never booted otherwise).
 *
 * GET /v1/ping and /v1/me deliberately stay in routes/api.php, not here — they're
 * generic authenticated-envelope endpoints with no mobile-specific logic (used by
 * EnvelopeTest as canonical examples of the response shape), not part of the mobile
 * domain the way device registration and sync are.
 *
 * Deliberately does NOT relocate controllers/models/services/requests — those stay
 * in their existing app/ locations, same scope decision as the Billing module.
 */
class MobileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MobileAuthServiceInterface::class, MobileAuthService::class);
        $this->app->bind(SyncProcessorInterface::class, SyncProcessor::class);
        $this->app->singleton(SyncRegistry::class);
    }

    public function boot(): void
    {
        // routes/api.php's routes get the `api` prefix + middleware group automatically
        // via bootstrap/app.php's withRouting(api: ...) — that automatic wrapping only
        // applies to the one file passed there, so a module route file loaded via
        // loadRoutesFrom() from an arbitrary provider has to declare it explicitly to
        // end up with the same api/v1/... URI and api middleware stack.
        Route::prefix('api')->middleware('api')->group(function () {
            $this->loadRoutesFrom(base_path('routes/modules/mobile-api.php'));
        });

        $this->loadMigrationsFrom(database_path('migrations/modules/mobile'));

        RateLimiter::for('mobile-auth', function (Request $request) {
            return Limit::perMinute((int) config('authx.throttle.login_per_account', 5))
                ->by($request->string('email')->toString().'|'.$request->ip());
        });
    }
}
