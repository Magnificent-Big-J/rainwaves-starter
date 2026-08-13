<?php

namespace Tests\Feature\Api\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_index_requires_roles_view_permission(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        $this->actingAs($customer)
            ->getJson('/api/v1/roles')
            ->assertStatus(403);
    }

    public function test_index_lists_roles_with_permissions_and_available_permission_options(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();

        $response = $this->actingAs($owner)->getJson('/api/v1/roles');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'admin')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'is_locked', 'permissions', 'users_count']],
                'meta' => ['options' => ['permissions']],
            ]);

        $this->assertNotEmpty($response->json('meta.options.permissions'));
    }

    public function test_view_only_admin_cannot_update_role_permissions(): void
    {
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();
        $staffRole = Role::where('name', 'staff')->firstOrFail();

        $this->actingAs($ops)
            ->putJson("/api/v1/roles/{$staffRole->id}/permissions", ['permissions' => []])
            ->assertStatus(403);
    }

    public function test_super_admin_can_update_a_non_locked_roles_permissions(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $staffRole = Role::where('name', 'staff')->firstOrFail();

        $this->actingAs($owner)
            ->putJson("/api/v1/roles/{$staffRole->id}/permissions", ['permissions' => ['dashboard.view', 'payments.view']])
            ->assertOk()
            ->assertJsonPath('data.permissions', ['dashboard.view', 'payments.view']);

        $this->assertSame(['dashboard.view', 'payments.view'], $staffRole->fresh()->permissions->pluck('name')->sort()->values()->all());
    }

    public function test_super_admin_role_permissions_cannot_be_changed(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $superAdminRole = Role::where('name', 'super-admin')->firstOrFail();
        $originalCount = $superAdminRole->permissions()->count();

        $this->actingAs($owner)
            ->putJson("/api/v1/roles/{$superAdminRole->id}/permissions", ['permissions' => []])
            ->assertStatus(422);

        $this->assertSame($originalCount, $superAdminRole->fresh()->permissions()->count());
    }

    public function test_rejects_an_unknown_permission_name(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $staffRole = Role::where('name', 'staff')->firstOrFail();

        $this->actingAs($owner)
            ->putJson("/api/v1/roles/{$staffRole->id}/permissions", ['permissions' => ['not-a-real-permission']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['permissions.0']);
    }
}
