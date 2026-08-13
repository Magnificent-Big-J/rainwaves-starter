<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ActivityLogResource;
use App\Http\Responses\Envelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 25), 100));
        $logName = $request->string('log_name')->toString() ?: null;
        $search = $request->string('search')->toString() ?: null;

        $activity = Activity::query()
            ->with('causer')
            ->when($logName, fn ($query) => $query->where('log_name', $logName))
            ->when($search, fn ($query) => $query->where('description', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage);

        return Envelope::success(ActivityLogResource::collection($activity), '', [
            'options' => [
                'log_names' => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name')->filter()->values()->all(),
            ],
        ]);
    }
}
