<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\CollectionExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ActivityLogResource;
use App\Http\Responses\Envelope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityLogController extends Controller
{
    // Same defensive cap as UserAdminService::EXPORT_ROW_LIMIT — a synchronous
    // export shouldn't be able to pull an unbounded activity log into memory.
    private const EXPORT_ROW_LIMIT = 10000;

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 25), 100));
        $logName = $request->string('log_name')->toString() ?: null;
        $search = $request->string('search')->toString() ?: null;

        $activity = $this->filteredQuery($logName, $search)->paginate($perPage);

        return Envelope::success(ActivityLogResource::collection($activity), '', [
            'options' => [
                'log_names' => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name')->filter()->values()->all(),
            ],
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $logName = $request->string('log_name')->toString() ?: null;
        $search = $request->string('search')->toString() ?: null;

        $activity = $this->filteredQuery($logName, $search)->limit(self::EXPORT_ROW_LIMIT)->get();

        return Excel::download(
            new CollectionExport(
                $activity,
                ['Event', 'Log', 'By', 'When'],
                fn (Activity $entry) => [
                    $entry->description,
                    $entry->log_name,
                    $entry->causer->name ?? 'System',
                    $entry->created_at?->toDateTimeString(),
                ],
            ),
            'audit-log.xlsx',
        );
    }

    private function filteredQuery(?string $logName, ?string $search): Builder
    {
        return Activity::query()
            ->with('causer')
            ->when($logName, fn ($query) => $query->where('log_name', $logName))
            ->when($search, fn ($query) => $query->where('description', 'like', "%{$search}%"))
            ->latest();
    }
}
