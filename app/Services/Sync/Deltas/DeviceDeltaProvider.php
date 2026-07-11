<?php

namespace App\Services\Sync\Deltas;

use App\Contracts\Sync\DeltaProvider;
use App\Models\Device;
use App\Models\SyncTombstone;
use App\Models\User;
use App\Services\Sync\Handlers\DeviceSyncHandler;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Reference DeltaProvider implementation (see DeviceSyncHandler). */
class DeviceDeltaProvider implements DeltaProvider
{
    public function query(User $user, CarbonImmutable $since): Builder
    {
        return Device::query()
            ->whereBelongsTo($user)
            ->where('updated_at', '>', $since);
    }

    public function tombstones(User $user, CarbonImmutable $since): Builder
    {
        return SyncTombstone::query()
            ->resource(DeviceSyncHandler::RESOURCE)
            ->where('user_id', $user->getKey())
            ->deletedSince($since);
    }

    /** @param  Device  $record */
    public function serialize(Model $record): array
    {
        return [
            'uuid' => $record->uuid,
            'platform' => $record->platform->value,
            'model' => $record->model,
            'os_version' => $record->os_version,
            'app_version' => $record->app_version,
            'last_seen_at' => $record->last_seen_at?->toIso8601String(),
            'updated_at' => $record->updated_at?->toIso8601String(),
        ];
    }
}
