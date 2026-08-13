<?php

namespace App\Modules\Mobile;

use App\Modules\ModuleManifest;

class MobileModule implements ModuleManifest
{
    public function name(): string
    {
        return 'mobile';
    }

    public function permissions(): array
    {
        return [];
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
