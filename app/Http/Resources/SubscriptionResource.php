<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_payment_id' => $this->merchant_payment_id,
            'item_name' => $this->item_name,
            'recurring_amount' => $this->recurring_amount,
            'billing_date' => $this->billing_date?->toDateString(),
            'frequency' => $this->frequency,
            'cycles' => $this->cycles,
            'provider' => $this->provider,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'is_terminal' => $this->status->isTerminal(),
            'initiated_at' => $this->initiated_at?->toIso8601String(),
            'activated_at' => $this->activated_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
        ];
    }
}
