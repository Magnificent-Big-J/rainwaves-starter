<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Sync\SyncProcessorInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sync\SyncDeltaRequest;
use App\Http\Requests\Sync\SyncOperationsRequest;
use App\Http\Responses\Envelope;
use App\Services\Sync\SyncRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    public function __construct(
        private readonly SyncProcessorInterface $processor,
        private readonly SyncRegistry $registry,
    ) {}

    /**
     * Ingest a batch of offline operations. Always 200; each operation
     * carries its own status (applied | conflict | failed) in the results.
     */
    public function operations(SyncOperationsRequest $request): JsonResponse
    {
        $results = $this->processor->process(
            $request->user(),
            $request->validated('operations'),
        );

        return Envelope::success(['results' => $results]);
    }

    /**
     * Delta read: changed records + tombstones per resource since a cursor.
     * Clients advance their cursor to meta.server_time once every resource
     * reports has_more=false, then re-poll with the same cursor otherwise.
     */
    public function delta(SyncDeltaRequest $request): JsonResponse
    {
        $user = $request->user();
        $since = CarbonImmutable::parse($request->validated('since'));
        $limit = (int) config('sync.delta_limit', 500);
        $serverTime = now()->toIso8601String();

        $changes = [];

        foreach ($request->resourceList() as $resource) {
            $provider = $this->registry->deltaFor($resource);

            $records = $provider->query($user, $since)
                ->orderBy('updated_at')
                ->limit($limit + 1)
                ->get();

            $hasMore = $records->count() > $limit;
            $records = $records->take($limit);

            $changes[$resource] = [
                'records' => $records->map(fn ($record) => $provider->serialize($record))->values(),
                'tombstones' => $provider->tombstones($user, $since)
                    ->orderBy('deleted_at')
                    ->limit($limit)
                    ->get()
                    ->map(fn ($tombstone) => [
                        'resource_id' => $tombstone->resource_id,
                        'deleted_at' => $tombstone->deleted_at->toIso8601String(),
                    ])
                    ->values(),
                'has_more' => $hasMore,
                'cursor' => $hasMore ? $records->last()?->updated_at?->toIso8601String() : null,
            ];
        }

        return Envelope::success(['changes' => $changes], '', ['server_time' => $serverTime]);
    }
}
