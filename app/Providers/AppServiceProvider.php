<?php

namespace App\Providers;

use App\Contracts\PayFastCheckoutServiceInterface;
use App\Contracts\UserAdminServiceInterface;
use App\Listeners\LogSecurityActivity;
use App\Modules\ModuleRegistry;
use App\Services\PayFastCheckoutService;
use App\Services\UserAdminService;
use Illuminate\Support\Facades\Event;
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
        $this->app->singleton(ModuleRegistry::class);
    }

    public function boot(): void
    {
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
