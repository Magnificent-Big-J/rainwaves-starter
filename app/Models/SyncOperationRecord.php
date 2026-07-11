<?php

namespace App\Models;

use App\Enums\SyncOperationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ledger of processed sync operations. Provides idempotency (replayed op ids
 * return their stored result) and an audit trail of the offline queue.
 */
class SyncOperationRecord extends Model
{
    protected $table = 'sync_operations';

    protected $fillable = [
        'op_id',
        'user_id',
        'resource',
        'type',
        'client_id',
        'server_id',
        'status',
        'errors',
        'occurred_at',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SyncOperationStatus::class,
            'errors' => 'array',
            'occurred_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array{id: string, status: string, server_id: ?string, errors: object} */
    public function toResultArray(): array
    {
        return [
            'id' => $this->op_id,
            'status' => $this->status->value,
            'server_id' => $this->server_id,
            'errors' => (object) ($this->errors ?? []),
        ];
    }
}
