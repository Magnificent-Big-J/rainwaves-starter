<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Modules\Teams\Models\Team;
use App\Modules\Teams\Models\TeamMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTeamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_a_customer_cannot_view_the_admin_teams_overview(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        $this->actingAs($customer)
            ->getJson('/api/v1/admin/teams')
            ->assertForbidden();
    }

    public function test_an_admin_can_view_every_team_regardless_of_membership(): void
    {
        $admin = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $outsider = User::factory()->create();
        $team = $this->createTeamFor($outsider);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/teams')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Test Team']);

        $this->actingAs($admin)
            ->getJson("/api/v1/admin/teams/{$team->id}")
            ->assertOk()
            ->assertJsonPath('data.team.name', 'Test Team')
            ->assertJsonCount(1, 'data.members');
    }

    private function createTeamFor(User $owner): Team
    {
        $team = Team::query()->create([
            'name' => 'Test Team',
            'slug' => 'test-team-'.$owner->id,
            'owner_id' => $owner->id,
        ]);

        TeamMembership::query()->create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return $team;
    }
}
