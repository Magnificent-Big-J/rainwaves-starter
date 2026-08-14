<?php

namespace App\Modules\Governance\Models;

use App\Models\User;
use App\Modules\Governance\Enums\RoleChangeRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleChangeRequest extends Model
{
    protected $fillable = [
        'user_id',
        'requested_roles',
        'previous_roles',
        'requested_by',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_roles' => 'array',
            'previous_roles' => 'array',
            'status' => RoleChangeRequestStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === RoleChangeRequestStatus::Pending;
    }
}
