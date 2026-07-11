<?php

namespace App\Contracts\Sync;

use App\DTO\Sync\SyncOperation;
use App\DTO\Sync\SyncResult;
use App\Exceptions\SyncConflictException;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Applies client sync operations for one resource. Registered per resource
 * name in config/sync.php; apply() runs inside a DB transaction and must be
 * idempotent for creates (client_id upsert).
 */
interface SyncResourceHandler
{
    /**
     * @throws SyncConflictException when server state wins
     * @throws ValidationException on invalid payload
     */
    public function apply(SyncOperation $operation, User $user): SyncResult;
}
