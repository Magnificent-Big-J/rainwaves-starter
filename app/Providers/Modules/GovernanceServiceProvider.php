<?php

namespace App\Providers\Modules;

use App\Contracts\UserAdminServiceInterface;
use App\Modules\Governance\Contracts\GovernanceServiceInterface;
use App\Modules\Governance\Contracts\LegalServiceInterface;
use App\Modules\Governance\Services\ApprovalGatedUserAdminService;
use App\Modules\Governance\Services\GovernanceService;
use App\Modules\Governance\Services\LegalService;
use App\Services\UserAdminService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The Governance module — only ever instantiated when MODULE_GOVERNANCE_ENABLED
 * evaluates true (see bootstrap/providers.php). Declares no dependencies() — legal
 * consent and role-elevation approval are unrelated to Billing/Teams, and the data
 * export feature degrades gracefully via runtime config('modules.*') checks rather
 * than requiring either.
 *
 * Rebinds UserAdminServiceInterface to a decorator (ApprovalGatedUserAdminService,
 * wrapping the real UserAdminService concretely) that intercepts role-elevation
 * requests. This only takes effect when this provider is registered — since
 * bootstrap/providers.php appends module providers after AppServiceProvider, this
 * binding runs after (and so overrides) AppServiceProvider's own bind() for the same
 * interface. UserAdminController/UserAdminService themselves stay completely
 * unmodified and unaware Governance exists.
 */
class GovernanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LegalServiceInterface::class, LegalService::class);
        $this->app->bind(GovernanceServiceInterface::class, GovernanceService::class);

        $this->app->bind(UserAdminServiceInterface::class, function ($app) {
            return new ApprovalGatedUserAdminService($app->make(UserAdminService::class));
        });
    }

    public function boot(): void
    {
        Route::prefix('api')->middleware('api')->group(function () {
            $this->loadRoutesFrom(base_path('routes/modules/governance-api.php'));
        });

        $this->loadMigrationsFrom(database_path('migrations/modules/governance'));
    }
}
