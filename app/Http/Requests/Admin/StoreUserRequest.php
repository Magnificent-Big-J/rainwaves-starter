<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $hasRolesTable = Schema::hasTable('roles');
        $hasPermissionsTable = Schema::hasTable('permissions');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'roles' => ['sometimes', 'array'],
            'roles.*' => $hasRolesTable ? ['string', 'exists:roles,name'] : ['string'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => $hasPermissionsTable ? ['string', 'exists:permissions,name'] : ['string'],
        ];
    }
}
