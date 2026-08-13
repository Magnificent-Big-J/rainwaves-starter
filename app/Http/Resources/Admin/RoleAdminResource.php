<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

/** @mixin Role */
class RoleAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_locked' => $this->name === 'super-admin',
            'permissions' => $this->permissions->pluck('name')->values()->all(),
            'users_count' => $this->whenCounted('users'),
        ];
    }
}
