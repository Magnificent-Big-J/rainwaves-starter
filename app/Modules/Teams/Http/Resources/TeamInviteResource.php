<?php

namespace App\Modules\Teams\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamInviteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'invited_by' => $this->inviter->name,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
        ];
    }
}
