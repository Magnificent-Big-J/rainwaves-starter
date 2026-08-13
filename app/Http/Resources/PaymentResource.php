<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_payment_id' => $this->merchant_payment_id,
            'item_name' => $this->item_name,
            'item_description' => $this->item_description,
            'amount_requested' => $this->amount_requested,
            'amount_gross' => $this->amount_gross,
            'customer_name' => trim(($this->customer_first_name ?? '').' '.($this->customer_last_name ?? '')) ?: null,
            'provider' => $this->provider,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'initiated_at' => $this->initiated_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
    }
}
