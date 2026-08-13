<?php

namespace App\Modules\Teams\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'owner' => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
            ],
            // Controllers eager-load withCount('members') for the list/show paths this
            // resource backs; the fallback query only ever runs if a future caller forgets to.
            'member_count' => $this->members_count ?? $this->members()->count(),
            'max_members' => $this->maxMembers(),
            'created_at' => $this->created_at,
        ];
    }
}
