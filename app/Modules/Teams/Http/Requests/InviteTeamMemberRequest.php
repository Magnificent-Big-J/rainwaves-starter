<?php

namespace App\Modules\Teams\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            // Owner is deliberately not an assignable invite role — see TeamService::createInvite().
            'role' => ['required', 'string', Rule::in(['admin', 'member'])],
        ];
    }
}
