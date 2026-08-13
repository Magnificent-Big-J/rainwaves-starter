<?php

namespace App\Providers\Modules;

use App\Modules\Teams\Contracts\TeamServiceInterface;
use App\Modules\Teams\Services\TeamService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The Teams module — only ever instantiated when MODULE_TEAMS_ENABLED evaluates true
 * (see bootstrap/providers.php, the actual on/off switch). Depends on Billing
 * (TeamsModule::dependencies()) — a team's plan/subscription belongs to the team, and
 * Team::maxMembers() reads a plan's member cap from config('billing-plans'). This is
 * the module registry's first real cross-module dependency: enabling Teams while
 * Billing is disabled throws via ModuleRegistry, proven live in PresetInstallMatrixTest.
 */
class TeamsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TeamServiceInterface::class, TeamService::class);
    }

    public function boot(): void
    {
        // routes/api.php's routes get the `api` prefix + middleware group automatically
        // via bootstrap/app.php's withRouting(api: ...) — that automatic wrapping only
        // applies to the one file passed there, so a module route file loaded via
        // loadRoutesFrom() from an arbitrary provider has to declare it explicitly to
        // end up with the same api/v1/... URI and api middleware stack.
        Route::prefix('api')->middleware('api')->group(function () {
            $this->loadRoutesFrom(base_path('routes/modules/teams-api.php'));
        });

        $this->loadMigrationsFrom(database_path('migrations/modules/teams'));
    }
}
