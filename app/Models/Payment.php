<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
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
            'status'       => PaymentStatus::class,
            'metadata'     => 'array',
            'initiated_at' => 'datetime',
            'paid_at'      => 'datetime',
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
