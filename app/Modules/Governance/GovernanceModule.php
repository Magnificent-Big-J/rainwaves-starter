<?php

namespace App\Modules\Governance;

use App\Modules\ModuleManifest;

class GovernanceModule implements ModuleManifest
{
    public function name(): string
    {
        return 'governance';
    }

    public function permissions(): array
    {
        return ['governance.manage'];
    }

    /**
     * No hard dependency — legal consent and role-elevation approval are unrelated to
     * Billing/Teams. Data export conditionally includes their data via a runtime
     * config('modules.*') check, not a module dependency (see GovernanceService).
     */
    public function dependencies(): array
    {
        return [];
    }

    public function conflicts(): array
    {
        return [];
    }
}
