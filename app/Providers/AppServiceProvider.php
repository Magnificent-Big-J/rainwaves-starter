<?php

namespace App\Providers;

use App\Contracts\MobileAuthServiceInterface;
use App\Contracts\PayFastCheckoutServiceInterface;
use App\Contracts\Sync\SyncProcessorInterface;
use App\Contracts\UserAdminServiceInterface;
use App\Services\MobileAuthService;
use App\Services\PayFastCheckoutService;
use App\Services\Sync\SyncProcessor;
use App\Services\Sync\SyncRegistry;
use App\Services\UserAdminService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserAdminServiceInterface::class, UserAdminService::class);
        $this->app->bind(PayFastCheckoutServiceInterface::class, PayFastCheckoutService::class);
        $this->app->bind(MobileAuthServiceInterface::class, MobileAuthService::class);
        $this->app->bind(SyncProcessorInterface::class, SyncProcessor::class);
        $this->app->singleton(SyncRegistry::class);
    }

    public function boot(): void
    {
        RateLimiter::for('mobile-auth', function (Request $request) {
            return Limit::perMinute((int) config('authx.throttle.login', 5))
                ->by($request->string('email')->toString().'|'.$request->ip());
        });
    }
}
