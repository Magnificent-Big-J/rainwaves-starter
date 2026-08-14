<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Modules\Governance\Models\RoleChangeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_granting_admin_to_a_user_files_a_pending_request_instead_of_applying_immediately(): void
    {
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();
        $target = User::factory()->create();
        $target->assignRole('customer');

        $response = $this->actingAs($ops)
            ->patchJson("/api/v1/users/{$target->id}", ['roles' => ['customer', 'admin']])
            ->assertOk();

        // Update applied immediately, but without the elevated role.
        $this->assertSame(['customer'], $response->json('data.roles'));
        $this->assertFalse($target->fresh()->hasRole('admin'));

        $this->assertDatabaseHas('role_change_requests', [
            'user_id' => $target->id,
            'requested_by' => $ops->id,
            'status' => 'pending',
        ]);

        $stored = RoleChangeRequest::query()->where('user_id', $target->id)->firstOrFail();
        $this->assertSame(['customer', 'admin'], $stored->requested_roles);
        $this->assertSame(['customer'], $stored->previous_roles);
    }

    public function test_non_elevation_role_changes_still_apply_immediately(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $target = User::factory()->create();
        $target->assignRole('customer');

        $response = $this->actingAs($owner)
            ->patchJson("/api/v1/users/{$target->id}", ['roles' => ['staff']])
            ->assertOk();

        $this->assertSame(['staff'], $response->json('data.roles'));
        $this->assertDatabaseMissing('role_change_requests', ['user_id' => $target->id]);
    }

    public function test_demoting_an_admin_applies_immediately_with_no_approval_needed(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();

        $this->actingAs($owner)
            ->patchJson("/api/v1/users/{$ops->id}", ['roles' => ['staff']])
            ->assertOk();

        $this->assertTrue($ops->fresh()->hasRole('staff'));
        $this->assertFalse($ops->fresh()->hasRole('admin'));
        $this->assertDatabaseMissing('role_change_requests', ['user_id' => $ops->id]);
    }

    public function test_creating_a_user_with_an_elevated_role_files_a_pending_request(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();

        $response = $this->actingAs($owner)
            ->postJson('/api/v1/users', [
                'name' => 'New Admin',
                'email' => 'new-admin@example.com',
                'password' => 'password123!',
                'password_confirmation' => 'password123!',
                'roles' => ['admin'],
            ])
            ->assertCreated();

        $this->assertSame([], $response->json('data.roles'));

        $newUser = User::where('email', 'new-admin@example.com')->firstOrFail();
        $this->assertFalse($newUser->hasRole('admin'));

        $this->assertDatabaseHas('role_change_requests', [
            'user_id' => $newUser->id,
            'requested_by' => $owner->id,
            'status' => 'pending',
        ]);
    }

    public function test_a_different_admin_can_approve_a_pending_elevation(): void
    {
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $target = User::factory()->create();
        $target->assignRole('customer');

        $this->actingAs($ops)->patchJson("/api/v1/users/{$target->id}", ['roles' => ['customer', 'admin']])->assertOk();
        $request = RoleChangeRequest::query()->where('user_id', $target->id)->firstOrFail();

        $this->actingAs($owner)
            ->postJson("/api/v1/governance/role-change-requests/{$request->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertTrue($target->fresh()->hasRole('admin'));
        $this->assertDatabaseHas('role_change_requests', ['id' => $request->id, 'status' => 'approved', 'approved_by' => $owner->id]);
    }

    public function test_a_pending_elevation_can_be_rejected(): void
    {
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $target = User::factory()->create();
        $target->assignRole('customer');

        $this->actingAs($ops)->patchJson("/api/v1/users/{$target->id}", ['roles' => ['customer', 'admin']])->assertOk();
        $request = RoleChangeRequest::query()->where('user_id', $target->id)->firstOrFail();

        $this->actingAs($owner)
            ->postJson("/api/v1/governance/role-change-requests/{$request->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertFalse($target->fresh()->hasRole('admin'));
    }

    public function test_the_requester_cannot_approve_their_own_request(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $target = User::factory()->create();
        $target->assignRole('customer');

        $this->actingAs($owner)->patchJson("/api/v1/users/{$target->id}", ['roles' => ['customer', 'admin']])->assertOk();
        $request = RoleChangeRequest::query()->where('user_id', $target->id)->firstOrFail();

        $this->actingAs($owner)
            ->postJson("/api/v1/governance/role-change-requests/{$request->id}/approve")
            ->assertStatus(422);

        $this->assertFalse($target->fresh()->hasRole('admin'));
    }

    public function test_an_already_decided_request_cannot_be_approved_again(): void
    {
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $target = User::factory()->create();
        $target->assignRole('customer');

        $this->actingAs($ops)->patchJson("/api/v1/users/{$target->id}", ['roles' => ['customer', 'admin']])->assertOk();
        $request = RoleChangeRequest::query()->where('user_id', $target->id)->firstOrFail();

        $this->actingAs($owner)->postJson("/api/v1/governance/role-change-requests/{$request->id}/approve")->assertOk();
        $this->actingAs($owner)->postJson("/api/v1/governance/role-change-requests/{$request->id}/approve")->assertStatus(422);
    }

    public function test_an_admin_without_governance_manage_cannot_list_or_approve_requests(): void
    {
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();
        $target = User::factory()->create();
        $target->assignRole('customer');

        $this->actingAs($ops)->patchJson("/api/v1/users/{$target->id}", ['roles' => ['customer', 'admin']])->assertOk();
        $request = RoleChangeRequest::query()->where('user_id', $target->id)->firstOrFail();

        // ops (plain admin) requested it, but doesn't hold governance.manage (super-admin
        // only) so can't see or act on the queue at all, on top of being the requester.
        $this->actingAs($ops)->getJson('/api/v1/governance/role-change-requests')->assertForbidden();
        $this->actingAs($ops)->postJson("/api/v1/governance/role-change-requests/{$request->id}/approve")->assertForbidden();
    }

    public function test_a_customer_cannot_reach_the_role_change_requests_queue(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        $this->actingAs($customer)->getJson('/api/v1/governance/role-change-requests')->assertForbidden();
    }
}
