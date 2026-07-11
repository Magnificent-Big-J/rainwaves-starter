<?php

namespace App\Contracts\Sync;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Supplies delta-sync reads for one resource: records changed since a cursor
 * plus tombstones for deletions, both scoped to what $user may see.
 */
interface DeltaProvider
{
    /** Records changed since $since, visible to $user. Ordered by the framework. */
    public function query(User $user, CarbonImmutable $since): Builder;

    /** Tombstones for this resource deleted since $since, visible to $user. */
    public function tombstones(User $user, CarbonImmutable $since): Builder;

    /** Serialize one changed record for the delta payload. */
    public function serialize(Model $record): array;
}
