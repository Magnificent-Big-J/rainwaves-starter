<?php

namespace App\Modules\Teams;

use App\Modules\ModuleManifest;

class TeamsModule implements ModuleManifest
{
    public function name(): string
    {
        return 'teams';
    }

    public function permissions(): array
    {
        return ['teams.view', 'teams.manage'];
    }

    /** Team billing (a team's plan/subscription) needs the Billing module enabled. */
    public function dependencies(): array
    {
        return ['billing'];
    }

    public function conflicts(): array
    {
        return [];
    }
}
