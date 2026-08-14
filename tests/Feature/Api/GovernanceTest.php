<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_export_returns_a_downloadable_json_file_of_the_callers_own_data(): void
    {
        $user = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        $response = $this->actingAs($user)->get('/api/v1/governance/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/json');

        $payload = json_decode($response->streamedContent(), true);

        $this->assertSame($user->id, $payload['profile']['id']);
        $this->assertSame($user->email, $payload['profile']['email']);
        $this->assertArrayHasKey('exported_at', $payload);
    }

    public function test_export_requires_authentication(): void
    {
        $this->getJson('/api/v1/governance/export')->assertUnauthorized();
    }

    public function test_a_customer_can_delete_their_own_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/api/v1/governance/account')
            ->assertOk();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_the_last_super_admin_cannot_delete_their_own_account(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();

        $this->actingAs($owner)
            ->deleteJson('/api/v1/governance/account')
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'deleted_at' => null]);
    }

    public function test_a_non_last_super_admin_can_delete_their_own_account(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $secondSuperAdmin = User::factory()->create();
        $secondSuperAdmin->assignRole('super-admin');

        $this->actingAs($secondSuperAdmin)
            ->deleteJson('/api/v1/governance/account')
            ->assertOk();

        $this->assertSoftDeleted('users', ['id' => $secondSuperAdmin->id]);
        $this->assertDatabaseHas('users', ['id' => $owner->id, 'deleted_at' => null]);
    }
}
