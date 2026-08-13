<?php

namespace App\Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiateSubscriptionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Server-side pricing authority: the client references a plan key from
            // config('billing-plans') — item_name/amount/frequency all come from
            // there, never from request input. See PayFastCheckoutService::resolvePlan().
            'plan' => ['required', 'string', Rule::in($this->availablePlans())],
            // Scheduling choices, not pricing — safe to stay caller-supplied.
            'billing_date' => ['required', 'date'],
            'cycles' => ['nullable', 'integer', 'min:0'],
            'name_first' => ['nullable', 'string', 'max:255'],
            'name_last' => ['nullable', 'string', 'max:255'],
            'email_address' => ['nullable', 'email'],
            'm_payment_id' => ['nullable', 'string', 'max:100'],
            // Only honored server-side when the caller has the payments.manage
            // permission (see PayFastController) — present here purely for input
            // shape validation, not an authorization decision.
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }

    /** @return list<string> */
    private function availablePlans(): array
    {
        return collect(config('billing-plans', []))
            ->filter(fn (array $plan) => ($plan['mode'] ?? null) === 'subscription')
            ->keys()
            ->all();
    }
}
