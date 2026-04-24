<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatarUrl = null;

        if (method_exists($this->resource, 'getFirstMediaUrl')) {
            $avatarUrl = $this->getFirstMediaUrl('avatar', 'avatar') ?: $this->getFirstMediaUrl('avatar');
            $avatarUrl = $avatarUrl !== '' ? $avatarUrl : null;
        }

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $avatarUrl,
        ];

        if (method_exists($this->resource, 'getRoleNames')) {
            $data['roles'] = $this->getRoleNames()->values()->all();
        }

        if (method_exists($this->resource, 'getAllPermissions')) {
            $data['permissions'] = $this->getAllPermissions()->pluck('name')->values()->all();
        }

        return $data;
    }
}
