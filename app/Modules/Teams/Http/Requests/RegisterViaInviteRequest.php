<?php

namespace App\Modules\Teams\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterViaInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public route — there's no user yet, that's the whole point of this endpoint.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
