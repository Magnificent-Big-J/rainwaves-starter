<?php

namespace App\Modules\Teams\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Wraps a TeamMembership row, flattening the related user for a table row. */
class TeamMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'is_owner' => $this->user_id === $this->team->owner_id,
            'joined_at' => $this->joined_at,
        ];
    }
}
