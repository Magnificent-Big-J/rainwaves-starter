<?php

namespace App\Modules\Governance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleChangeRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'requested_roles' => $this->requested_roles,
            'previous_roles' => $this->previous_roles,
            'requested_by' => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
                'email' => $this->requester->email,
            ],
            'status' => $this->status->value,
            'created_at' => $this->created_at,
        ];
    }
}
