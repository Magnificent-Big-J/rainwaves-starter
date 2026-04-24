<?php

namespace App\Providers;

use App\Contracts\PayFastCheckoutServiceInterface;
use App\Contracts\UserAdminServiceInterface;
use App\Services\PayFastCheckoutService;
use App\Services\UserAdminService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserAdminServiceInterface::class, UserAdminService::class);
        $this->app->bind(PayFastCheckoutServiceInterface::class, PayFastCheckoutService::class);
    }

    public function boot(): void
    {
        //
    }
}
