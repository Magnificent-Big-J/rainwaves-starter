<?php

namespace App\Http\Requests\Mobile;

use App\Services\MobileAuthService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Rainwaves\LaraAuthSuite\Support\Enums\TwoFactorChannel;

class TwoFactorChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $channels = [...array_column(TwoFactorChannel::cases(), 'value'), MobileAuthService::CHANNEL_RECOVERY];

        return [
            'pending_auth_id' => ['required', 'uuid'],
            'code' => ['required', 'string'],
            'channel' => ['sometimes', 'nullable', Rule::in($channels)],
        ];
    }
}
