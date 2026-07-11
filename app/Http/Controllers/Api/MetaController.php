<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\Envelope;
use App\Services\Sync\SyncRegistry;
use Illuminate\Http\JsonResponse;

/**
 * Mobile bootstrap: called on app start, before authentication, so clients
 * learn minimum version, feature flags, sync resources and option sets.
 */
class MetaController extends Controller
{
    public function show(SyncRegistry $registry): JsonResponse
    {
        $optionSets = collect(config('mobile.option_sets', []))
            ->map(fn (string $enum) => collect($enum::cases())
                ->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()])
                ->all());

        return Envelope::success([
            'min_app_version' => config('mobile.min_app_version'),
            'features' => (object) config('mobile.features', []),
            'sync_resources' => $registry->resources(),
            'option_sets' => $optionSets,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
