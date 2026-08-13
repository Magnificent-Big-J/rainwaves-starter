<?php

namespace App\Modules\Billing\Models;

use App\Enums\PaymentStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        // team_id/plan_key columns are owned by the Teams module's own migration (not
        // Billing's) — see database/migrations/modules/teams — but a plain nullable
        // int/string column here has no class-level dependency on Teams, so Payment
        // itself doesn't import anything from App\Modules\Teams. Deliberately no
        // team() relation here for the same reason (that would require importing
        // App\Modules\Teams\Models\Team, which would invert the module dependency
        // direction — Teams depends on Billing, never the reverse).
        'team_id',
        'plan_key',
        'merchant_payment_id',
        'provider',
        'item_name',
        'item_description',
        'amount_requested',
        'amount_gross',
        'amount_fee',
        'amount_net',
        'customer_first_name',
        'customer_last_name',
        'customer_email',
        'payfast_payment_id',
        'status',
        'payment_status',
        'metadata',
        'initiated_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'metadata' => 'array',
            'initiated_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }
}
