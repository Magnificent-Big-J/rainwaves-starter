<?php

namespace App\Modules\Billing;

use App\Modules\ModuleManifest;

class BillingModule implements ModuleManifest
{
    public function name(): string
    {
        return 'billing';
    }

    public function permissions(): array
    {
        return ['payments.view', 'payments.create', 'payments.manage'];
    }

    public function dependencies(): array
    {
        return [];
    }

    public function conflicts(): array
    {
        return [];
    }
}
