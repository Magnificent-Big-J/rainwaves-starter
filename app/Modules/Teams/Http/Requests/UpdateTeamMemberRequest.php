<?php

namespace App\Modules\Teams\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // Owner is deliberately not an assignable role — see TeamService::changeMemberRole().
            'role' => ['required', 'string', Rule::in(['admin', 'member'])],
        ];
    }
}
