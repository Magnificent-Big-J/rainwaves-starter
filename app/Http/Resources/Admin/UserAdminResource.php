<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

class UserAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canReadRoles = $this->permissionTablesReady();

        $roles = $canReadRoles && method_exists($this->resource, 'getRoleNames')
            ? $this->resource->getRoleNames()->values()->all()
            : [];

        $permissions = $canReadRoles && method_exists($this->resource, 'getAllPermissions')
            ? $this->resource->getAllPermissions()->pluck('name')->values()->all()
            : [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $roles,
            'permissions' => $permissions,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function permissionTablesReady(): bool
    {
        return Schema::hasTable('roles')
            && Schema::hasTable('permissions')
            && Schema::hasTable('model_has_roles')
            && Schema::hasTable('model_has_permissions');
    }
}
