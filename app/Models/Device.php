<?php

namespace App\Models;

use App\Enums\DevicePlatform;
use App\Models\Concerns\RecordsTombstones;
use App\Services\Sync\Handlers\DeviceSyncHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

class Device extends Model
{
    use RecordsTombstones;

    protected $fillable = [
        'uuid',
        'user_id',
        'platform',
        'model',
        'os_version',
        'app_version',
        'push_token',
        'personal_access_token_id',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'platform' => DevicePlatform::class,
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'personal_access_token_id');
    }

    public function syncResource(): string
    {
        return DeviceSyncHandler::RESOURCE;
    }

    public function syncResourceId(): string
    {
        return $this->uuid;
    }
}
