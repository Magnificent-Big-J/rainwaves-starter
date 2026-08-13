<?php

namespace App\Modules\Billing\Models;

use App\Enums\SubscriptionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        // See the identical comment in Payment.php — Teams-owned columns, deliberately
        // no team() relation here to keep the module dependency direction one-way.
        'team_id',
        'plan_key',
        'merchant_payment_id',
        'provider',
        'token',
        'item_name',
        'amount_requested',
        'recurring_amount',
        'billing_date',
        'frequency',
        'cycles',
        'status',
        'payment_status',
        'customer_first_name',
        'customer_last_name',
        'customer_email',
        'metadata',
        'initiated_at',
        'activated_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'metadata' => 'array',
            'billing_date' => 'date',
            'initiated_at' => 'datetime',
            'activated_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
