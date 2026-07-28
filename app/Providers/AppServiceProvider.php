<?php

namespace App\Providers;

use App\Contracts\MobileAuthServiceInterface;
use App\Contracts\PayFastCheckoutServiceInterface;
use App\Contracts\Sync\SyncProcessorInterface;
use App\Contracts\UserAdminServiceInterface;
use App\Listeners\LogSecurityActivity;
use App\Services\MobileAuthService;
use App\Services\PayFastCheckoutService;
use App\Services\Sync\SyncProcessor;
use App\Services\Sync\SyncRegistry;
use App\Services\UserAdminService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Rainwaves\LaraAuthSuite\Domain\Events\AuthenticationRateLimited;
use Rainwaves\LaraAuthSuite\Domain\Events\AuthenticationStateRevoked;
use Rainwaves\LaraAuthSuite\Domain\Events\CredentialsChanged;
use Rainwaves\LaraAuthSuite\Domain\Events\LoginFailed;
use Rainwaves\LaraAuthSuite\Domain\Events\PasswordResetCompleted;
use Rainwaves\LaraAuthSuite\Domain\Events\RecoveryCodesRegenerated;
use Rainwaves\LaraAuthSuite\Domain\Events\RecoveryCodeUsed;
use Rainwaves\LaraAuthSuite\Domain\Events\TwoFactorChallenged;
use Rainwaves\LaraAuthSuite\Domain\Events\TwoFactorDisabled;
use Rainwaves\LaraAuthSuite\Domain\Events\TwoFactorEnabled;
use Rainwaves\LaraAuthSuite\Domain\Events\TwoFactorSetupRequired;
use Rainwaves\LaraAuthSuite\Domain\Events\TwoFactorVerified;
use Rainwaves\LaraAuthSuite\Domain\Events\UserLoggedIn;
use Rainwaves\LaraAuthSuite\Domain\Events\UserRegistered;

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
            return Limit::perMinute((int) config('authx.throttle.login_per_account', 5))
                ->by($request->string('email')->toString().'|'.$request->ip());
        });

        $this->registerSecurityAuditListeners();
    }

    private function registerSecurityAuditListeners(): void
    {
        $events = [
            UserLoggedIn::class,
            UserRegistered::class,
            PasswordResetCompleted::class,
            TwoFactorChallenged::class,
            TwoFactorVerified::class,
            CredentialsChanged::class,
            AuthenticationStateRevoked::class,
            TwoFactorEnabled::class,
            TwoFactorDisabled::class,
            RecoveryCodesRegenerated::class,
            AuthenticationRateLimited::class,
            LoginFailed::class,
            TwoFactorSetupRequired::class,
            RecoveryCodeUsed::class,
        ];

        foreach ($events as $event) {
            Event::listen($event, LogSecurityActivity::class);
        }
    }
}
