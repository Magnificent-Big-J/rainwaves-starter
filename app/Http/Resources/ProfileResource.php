<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canReadPermissions = $this->permissionTablesReady();

        $roles = $canReadPermissions && method_exists($this->resource, 'getRoleNames')
            ? $this->resource->getRoleNames()->values()->all()
            : [];

        $permissions = $canReadPermissions && method_exists($this->resource, 'getAllPermissions')
            ? $this->resource->getAllPermissions()->pluck('name')->values()->all()
            : [];

        $avatarUrl = null;

        if (Schema::hasTable('media') && method_exists($this->resource, 'getFirstMediaUrl')) {
            $url = $this->getFirstMediaUrl('avatar', 'avatar') ?: $this->getFirstMediaUrl('avatar');
            $avatarUrl = $url !== '' ? $url : null;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $avatarUrl,
            'roles' => $roles,
            'permissions' => $permissions,
            'email_verified_at' => $this->email_verified_at,
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
