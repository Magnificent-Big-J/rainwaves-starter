<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SyncTombstone extends Model
{
    protected $fillable = [
        'resource',
        'resource_id',
        'user_id',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    public function scopeResource(Builder $query, string $resource): Builder
    {
        return $query->where('resource', $resource);
    }

    public function scopeDeletedSince(Builder $query, \DateTimeInterface $since): Builder
    {
        return $query->where('deleted_at', '>', $since);
    }
}
