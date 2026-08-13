<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\Envelope;
use App\Modules\ModuleRegistry;
use Illuminate\Http\JsonResponse;

/**
 * RS-101/RS-102: public bootstrap for the SPA's brand and navigation chrome. Same
 * payload for every visitor (guest or authenticated) — permission/role/environment/
 * module filtering of nav items happens client-side against the session already
 * loaded via GET /api/v1/me, so this stays a single cacheable, unauthenticated
 * response.
 */
class WebConfigController extends Controller
{
    public function __construct(private readonly ModuleRegistry $modules) {}

    public function show(): JsonResponse
    {
        return Envelope::success([
            'brand' => config('app-brand'),
            'features' => config('features'),
            'navigation' => config('navigation'),
            'environment' => app()->environment(),
            // RS-301: which modules are enabled, so the frontend can hide module-owned
            // nav items and skip fetching module-owned data (e.g. billing widgets on
            // dashboard.vue/customer/home.vue) without needing to know why.
            'modules' => [
                'billing' => $this->modules->isEnabled('billing'),
                'mobile' => $this->modules->isEnabled('mobile'),
            ],
        ]);
    }
}
