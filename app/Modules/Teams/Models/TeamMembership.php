<?php

namespace App\Modules\Teams\Models;

use App\Models\User;
use App\Modules\Teams\Enums\TeamRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMembership extends Model
{
    protected $fillable = ['team_id', 'user_id', 'role', 'joined_at'];

    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
            'joined_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
