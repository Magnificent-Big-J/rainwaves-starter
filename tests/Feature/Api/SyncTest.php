<?php

namespace Tests\Feature\Api;

use App\Enums\SyncOperationStatus;
use App\Models\Device;
use App\Models\SyncOperationRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SyncTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::where('email', 'customer@rainwaves.test')->firstOrFail();
        Sanctum::actingAs($this->user);
    }

    private function operation(array $overrides = []): array
    {
        return array_merge([
            'id' => (string) Str::uuid(),
            'type' => 'create',
            'resource' => 'devices',
            'client_id' => (string) Str::uuid(),
            'payload' => ['platform' => 'android', 'model' => 'Pixel 8'],
            'occurred_at' => now()->subMinute()->toIso8601String(),
        ], $overrides);
    }

    private function postOperations(array $operations)
    {
        return $this->postJson('/api/v1/sync/operations', ['operations' => $operations]);
    }

    public function test_create_operation_is_applied_and_returns_server_id(): void
    {
        $op = $this->operation();

        $this->postOperations([$op])
            ->assertOk()
            ->assertJsonPath('data.results.0.id', $op['id'])
            ->assertJsonPath('data.results.0.status', SyncOperationStatus::Applied->value)
            ->assertJsonPath('data.results.0.server_id', $op['client_id']);

        $this->assertDatabaseHas('devices', ['uuid' => $op['client_id'], 'user_id' => $this->user->id]);
        $this->assertDatabaseHas('sync_operations', ['op_id' => $op['id'], 'status' => 'applied']);
    }

    public function test_replaying_an_operation_returns_the_stored_result_without_reapplying(): void
    {
        $op = $this->operation();

        $this->postOperations([$op])->assertJsonPath('data.results.0.status', 'applied');

        $this->travel(5)->minutes();

        $this->postOperations([$op])
            ->assertOk()
            ->assertJsonPath('data.results.0.status', 'applied')
            ->assertJsonPath('data.results.0.server_id', $op['client_id']);

        $this->assertSame(1, Device::where('uuid', $op['client_id'])->count());
        $this->assertSame(1, SyncOperationRecord::where('user_id', $this->user->id)->count());
    }

    public function test_stale_update_is_reported_as_conflict(): void
    {
        $device = $this->user->devices()->create([
            'uuid' => (string) Str::uuid(),
            'platform' => 'android',
        ]);

        $staleVersion = $device->updated_at->copy()->subDay()->toIso8601String();

        $op = $this->operation([
            'type' => 'update',
            'client_id' => null,
            'resource_id' => $device->uuid,
            'payload' => ['model' => 'Stale Phone', 'version' => $staleVersion],
        ]);

        $this->postOperations([$op])
            ->assertOk()
            ->assertJsonPath('data.results.0.status', SyncOperationStatus::Conflict->value)
            ->assertJsonStructure(['data' => ['results' => [['errors' => ['version']]]]]);

        $this->assertNotSame('Stale Phone', $device->fresh()->model);
    }

    public function test_update_of_missing_record_is_a_conflict(): void
    {
        $op = $this->operation([
            'type' => 'update',
            'client_id' => null,
            'resource_id' => (string) Str::uuid(),
            'payload' => ['model' => 'Ghost'],
        ]);

        $this->postOperations([$op])
            ->assertJsonPath('data.results.0.status', SyncOperationStatus::Conflict->value);
    }

    public function test_invalid_payload_fails_only_that_operation(): void
    {
        $bad = $this->operation(['payload' => ['platform' => 'blackberry']]);
        $good = $this->operation();

        $response = $this->postOperations([$bad, $good]);

        $response->assertOk()
            ->assertJsonPath('data.results.0.status', SyncOperationStatus::Failed->value)
            ->assertJsonPath('data.results.1.status', SyncOperationStatus::Applied->value);
    }

    public function test_unknown_resource_fails_per_operation_not_the_batch(): void
    {
        $unknown = $this->operation(['resource' => 'martian-rocks']);
        $good = $this->operation();

        $this->postOperations([$unknown, $good])
            ->assertOk()
            ->assertJsonPath('data.results.0.status', 'failed')
            ->assertJsonPath('data.results.1.status', 'applied');
    }

    public function test_operation_with_unmet_dependency_fails(): void
    {
        $op = $this->operation(['depends_on' => [(string) Str::uuid()]]);

        $this->postOperations([$op])
            ->assertJsonPath('data.results.0.status', 'failed')
            ->assertJsonStructure(['data' => ['results' => [['errors' => ['depends_on']]]]]);
    }

    public function test_dependency_satisfied_within_the_same_batch(): void
    {
        $create = $this->operation();
        $update = $this->operation([
            'type' => 'update',
            'client_id' => null,
            'resource_id' => $create['client_id'],
            'payload' => ['model' => 'Renamed'],
            'depends_on' => [$create['id']],
        ]);

        $this->postOperations([$create, $update])
            ->assertJsonPath('data.results.0.status', 'applied')
            ->assertJsonPath('data.results.1.status', 'applied');

        $this->assertSame('Renamed', Device::where('uuid', $create['client_id'])->value('model'));
    }

    public function test_delete_is_idempotent_and_writes_a_tombstone(): void
    {
        $device = $this->user->devices()->create([
            'uuid' => (string) Str::uuid(),
            'platform' => 'ios',
        ]);

        $delete = $this->operation([
            'type' => 'delete',
            'client_id' => null,
            'resource_id' => $device->uuid,
            'payload' => [],
        ]);

        $this->postOperations([$delete])->assertJsonPath('data.results.0.status', 'applied');

        $this->assertDatabaseMissing('devices', ['uuid' => $device->uuid]);
        $this->assertDatabaseHas('sync_tombstones', ['resource' => 'devices', 'resource_id' => $device->uuid]);

        // Deleting again (new op id, already gone) is applied, not a conflict.
        $again = $this->operation([
            'type' => 'delete',
            'client_id' => null,
            'resource_id' => $device->uuid,
            'payload' => [],
        ]);

        $this->postOperations([$again])->assertJsonPath('data.results.0.status', 'applied');
    }

    public function test_delta_returns_changes_and_tombstones_since_cursor(): void
    {
        $since = now()->toIso8601String();

        $this->travel(1)->minutes();

        $kept = $this->user->devices()->create(['uuid' => (string) Str::uuid(), 'platform' => 'android']);
        $deleted = $this->user->devices()->create(['uuid' => (string) Str::uuid(), 'platform' => 'ios']);
        $deleted->delete();

        $response = $this->getJson('/api/v1/sync/delta?since='.urlencode($since).'&resources=devices');

        $response->assertOk()
            ->assertJsonPath('data.changes.devices.has_more', false)
            ->assertJsonPath('data.changes.devices.records.0.uuid', $kept->uuid)
            ->assertJsonPath('data.changes.devices.tombstones.0.resource_id', $deleted->uuid)
            ->assertJsonStructure(['meta' => ['server_time']]);
    }

    public function test_delta_rejects_unknown_resources(): void
    {
        $this->getJson('/api/v1/sync/delta?since='.urlencode(now()->toIso8601String()).'&resources=unicorns')
            ->assertStatus(422);
    }

    public function test_delta_does_not_leak_other_users_changes(): void
    {
        $other = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $since = now()->subMinute()->toIso8601String();

        $other->devices()->create(['uuid' => (string) Str::uuid(), 'platform' => 'android']);

        $this->getJson('/api/v1/sync/delta?since='.urlencode($since).'&resources=devices')
            ->assertOk()
            ->assertJsonCount(0, 'data.changes.devices.records');
    }

    public function test_batch_size_is_limited(): void
    {
        $operations = array_map(fn () => $this->operation(), range(1, config('sync.batch_max') + 1));

        $this->postOperations($operations)->assertStatus(422);
    }
}
