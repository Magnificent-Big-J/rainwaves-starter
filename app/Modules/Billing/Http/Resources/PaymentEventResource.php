<?php

namespace App\Modules\Billing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'provider' => $this->provider,
            'payment_id' => $this->payment_id,
            'subscription_id' => $this->subscription_id,
            'received_at' => $this->received_at?->toIso8601String(),
        ];
    }
}
