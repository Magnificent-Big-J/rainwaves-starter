<?php

namespace App\Models\Concerns;

use App\Models\SyncTombstone;

/**
 * Writes a sync tombstone whenever the model is deleted so delta sync can
 * propagate deletions to offline clients. Models define their sync resource
 * name and public identifier.
 */
trait RecordsTombstones
{
    public static function bootRecordsTombstones(): void
    {
        static::deleted(function (self $model) {
            SyncTombstone::create([
                'resource' => $model->syncResource(),
                'resource_id' => $model->syncResourceId(),
                'user_id' => $model->user_id ?? null,
                'deleted_at' => now(),
            ]);
        });
    }

    abstract public function syncResource(): string;

    /** Public identifier used by clients (uuid, not the internal PK). */
    public function syncResourceId(): string
    {
        return (string) $this->getKey();
    }
}
