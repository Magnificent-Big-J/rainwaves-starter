<?php

namespace Tests\Feature\Api\Admin;

use App\Exports\CollectionExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_index_requires_users_view_permission(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        $this->actingAs($customer)
            ->getJson('/api/v1/users')
            ->assertStatus(403);
    }

    public function test_index_excludes_archived_users_by_default(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();

        $ops->delete();

        $response = $this->actingAs($owner)->getJson('/api/v1/users');

        $response->assertOk();
        $this->assertNotContains('ops@rainwaves.test', collect($response->json('data'))->pluck('email')->all());
    }

    public function test_index_can_sort_by_name(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();

        $response = $this->actingAs($owner)->getJson('/api/v1/users?sort_by=name&sort_direction=asc&per_page=100');

        $names = collect($response->json('data'))->pluck('name')->all();
        $sorted = collect($names)->sort()->values()->all();

        $this->assertSame($sorted, $names);
    }

    public function test_can_archive_a_user(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();

        $this->actingAs($owner)
            ->deleteJson("/api/v1/users/{$ops->id}")
            ->assertOk()
            ->assertJsonPath('data.archived_at', fn ($value) => $value !== null);

        $this->assertSoftDeleted('users', ['id' => $ops->id]);
    }

    public function test_archiving_a_user_blocks_their_login(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();

        $this->actingAs($owner)->deleteJson("/api/v1/users/{$ops->id}")->assertOk();

        $this->assertNull(User::where('email', 'ops@rainwaves.test')->first());
    }

    public function test_cannot_archive_your_own_account(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();

        $this->actingAs($owner)
            ->deleteJson("/api/v1/users/{$owner->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'deleted_at' => null]);
    }

    public function test_cannot_archive_the_last_super_admin(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();
        $ops->syncRoles(['super-admin']);

        // Two super-admins now — archiving one should succeed.
        $this->actingAs($owner)
            ->deleteJson("/api/v1/users/{$ops->id}")
            ->assertOk();

        // Owner is now the only super-admin left. The seeder only grants users.delete
        // to super-admin, so simulate a broader permission grant (e.g. a project that
        // extends 'admin' with users.delete) to exercise the guard on a non-self actor.
        $secondActor = User::factory()->create();
        $secondActor->givePermissionTo(['users.view', 'users.delete']);

        $this->actingAs($secondActor)
            ->deleteJson("/api/v1/users/{$owner->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'deleted_at' => null]);
    }

    public function test_can_restore_an_archived_user(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();
        $ops->delete();

        $this->actingAs($owner)
            ->postJson("/api/v1/users/{$ops->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.archived_at', null);

        $this->assertDatabaseHas('users', ['id' => $ops->id, 'deleted_at' => null]);
    }

    public function test_status_filter_can_list_only_archived_users(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();
        $ops->delete();

        $response = $this->actingAs($owner)->getJson('/api/v1/users?status=archived');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.email', 'ops@rainwaves.test');
    }

    public function test_export_requires_users_view_permission(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        $this->actingAs($customer)
            ->get('/api/v1/users/export')
            ->assertStatus(403);
    }

    public function test_export_downloads_an_xlsx_file(): void
    {
        Excel::fake();

        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();

        $this->actingAs($owner)->get('/api/v1/users/export')->assertOk();

        Excel::assertDownloaded('users.xlsx');
    }

    public function test_export_respects_the_status_filter(): void
    {
        Excel::fake();

        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();
        $ops->delete();

        $this->actingAs($owner)->get('/api/v1/users/export?status=archived')->assertOk();

        Excel::assertDownloaded(
            'users.xlsx',
            fn (CollectionExport $export) => $export->collection()->pluck('email')->all() === ['ops@rainwaves.test'],
        );
    }
}
