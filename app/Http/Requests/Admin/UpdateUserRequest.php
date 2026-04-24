<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $hasRolesTable = Schema::hasTable('roles');
        $hasPermissionsTable = Schema::hasTable('permissions');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'roles' => ['sometimes', 'array'],
            'roles.*' => $hasRolesTable ? ['string', 'exists:roles,name'] : ['string'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => $hasPermissionsTable ? ['string', 'exists:permissions,name'] : ['string'],
        ];
    }
}
