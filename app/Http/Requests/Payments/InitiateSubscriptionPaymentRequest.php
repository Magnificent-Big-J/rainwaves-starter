<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;
use rainwaves\PayfastPayment\Model\Frequency;

class InitiateSubscriptionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'item_name' => ['required', 'string', 'max:255'],
            'billing_date' => ['required', 'date'],
            'recurring_amount' => ['required', 'numeric', 'min:0.01'],
            'frequency' => ['sometimes', 'integer', 'in:'.implode(',', [
                Frequency::DAILY,
                Frequency::WEEKLY,
                Frequency::MONTHLY,
                Frequency::QUARTERLY,
                Frequency::BI_ANNUAL,
                Frequency::ANNUAL,
            ])],
            'cycles' => ['nullable', 'integer', 'min:0'],
            'name_first' => ['nullable', 'string', 'max:255'],
            'name_last' => ['nullable', 'string', 'max:255'],
            'email_address' => ['nullable', 'email'],
            'm_payment_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
