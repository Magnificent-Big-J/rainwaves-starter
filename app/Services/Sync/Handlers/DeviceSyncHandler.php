<?php

namespace App\Services\Sync\Handlers;

use App\Contracts\Sync\SyncResourceHandler;
use App\DTO\Sync\SyncOperation;
use App\DTO\Sync\SyncResult;
use App\Enums\DevicePlatform;
use App\Enums\SyncOperationType;
use App\Exceptions\SyncConflictException;
use App\Models\Device;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Reference SyncResourceHandler implementation. Devices are a low-stakes
 * resource, but this demonstrates the full pattern apps must follow:
 * client_id upsert for creates, version-echo conflict detection for updates,
 * idempotent deletes with tombstones (via RecordsTombstones on the model).
 */
class DeviceSyncHandler implements SyncResourceHandler
{
    public const RESOURCE = 'devices';

    public function apply(SyncOperation $operation, User $user): SyncResult
    {
        return match (SyncOperationType::tryFrom($operation->type)) {
            SyncOperationType::Create => $this->create($operation, $user),
            SyncOperationType::Update => $this->update($operation, $user),
            SyncOperationType::Delete => $this->delete($operation, $user),
            default => SyncResult::failed(['type' => ["Unsupported operation type [{$operation->type}]."]]),
        };
    }

    private function create(SyncOperation $operation, User $user): SyncResult
    {
        $payload = $this->validatePayload($operation->payload, creating: true);

        $device = $user->devices()->updateOrCreate(
            ['uuid' => $operation->clientId],
            $payload + ['last_seen_at' => now()]
        );

        return SyncResult::applied($device->uuid);
    }

    private function update(SyncOperation $operation, User $user): SyncResult
    {
        $device = $user->devices()->where('uuid', $operation->resourceId)->first();

        if ($device === null) {
            throw new SyncConflictException([
                'resource' => ['The device no longer exists on the server.'],
            ]);
        }

        $this->guardVersion($operation, $device);

        $device->fill($this->validatePayload($operation->payload, creating: false))->save();

        return SyncResult::applied($device->uuid);
    }

    private function delete(SyncOperation $operation, User $user): SyncResult
    {
        $device = $user->devices()->where('uuid', $operation->resourceId)->first();

        // Already gone: deletes are idempotent, not conflicts.
        if ($device === null) {
            return SyncResult::applied($operation->resourceId);
        }

        if ($device->personal_access_token_id !== null) {
            PersonalAccessToken::whereKey($device->personal_access_token_id)->delete();
        }

        $device->delete();

        return SyncResult::applied($device->uuid);
    }

    /**
     * Version echo: the client sends the updated_at it last saw; if the
     * server row has moved past it, the client's edit is stale.
     */
    private function guardVersion(SyncOperation $operation, Device $device): void
    {
        $version = $operation->payload['version'] ?? null;

        if ($version !== null && $device->updated_at?->gt(CarbonImmutable::parse($version))) {
            throw new SyncConflictException([
                'version' => ['The server has newer changes for this device.'],
                'current' => [$device->updated_at->toIso8601String()],
            ]);
        }
    }

    private function validatePayload(array $payload, bool $creating): array
    {
        return Validator::make($payload, [
            'platform' => [$creating ? 'required' : 'sometimes', Rule::enum(DevicePlatform::class)],
            'model' => ['nullable', 'string', 'max:255'],
            'os_version' => ['nullable', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:255'],
            'push_token' => ['nullable', 'string', 'max:512'],
        ])->validate();
    }
}
