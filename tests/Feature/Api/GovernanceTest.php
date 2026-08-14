<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Modules\Teams\Models\Team;
use App\Modules\Teams\Models\TeamMembership;
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

    public function test_export_includes_team_and_payment_data_when_those_modules_are_enabled(): void
    {
        // Regression: exportDataFor() originally checked config('modules.teams')/
        // config('modules.billing') directly instead of the real
        // ModuleRegistry::isEnabled('teams'|'billing') path (config('modules.enabled.*')
        // is where module state actually lives) — both always resolved to null, so these
        // sections silently never appeared even with both modules enabled. Caught live in
        // a real browser, not by the original (looser) export test.
        $user = User::factory()->create();
        $team = Team::query()->create([
            'name' => 'Export Test Team',
            'slug' => 'export-test-team-'.$user->id,
            'owner_id' => $user->id,
        ]);
        TeamMembership::query()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/api/v1/governance/export');
        $payload = json_decode($response->streamedContent(), true);

        $this->assertArrayHasKey('team_memberships', $payload);
        $this->assertSame('Export Test Team', $payload['team_memberships'][0]['team']);
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
