<?php

namespace App\Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiateOneTimePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Server-side pricing authority: the client references a plan key from
            // config('billing-plans'), never sends amount/item_name directly — see
            // PayFastCheckoutService::resolvePlan().
            'plan' => ['required', 'string', Rule::in($this->availablePlans())],
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
            ->filter(fn (array $plan) => ($plan['mode'] ?? null) === 'payment')
            ->keys()
            ->all();
    }
}
