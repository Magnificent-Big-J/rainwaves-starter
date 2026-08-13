<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\Envelope;
use Illuminate\Http\JsonResponse;

/**
 * RS-101/RS-102: public bootstrap for the SPA's brand and navigation chrome. Same
 * payload for every visitor (guest or authenticated) — permission/role/environment
 * filtering of nav items happens client-side against the session already loaded via
 * GET /api/v1/me, so this stays a single cacheable, unauthenticated response.
 */
class WebConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return Envelope::success([
            'brand' => config('app-brand'),
            'features' => config('features'),
            'navigation' => config('navigation'),
            'environment' => app()->environment(),
        ]);
    }
}
