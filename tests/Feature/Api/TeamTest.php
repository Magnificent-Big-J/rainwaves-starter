<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Modules\Teams\Models\Team;
use App\Modules\Teams\Models\TeamMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_show_returns_null_team_when_the_caller_has_none(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/team')
            ->assertOk()
            ->assertJsonPath('data.team', null)
            ->assertJsonPath('data.my_role', null);
    }

    public function test_index_lists_only_teams_the_caller_belongs_to(): void
    {
        $user = User::factory()->create();
        $mine = $this->createTeamFor($user);
        $other = User::factory()->create();
        $this->createTeamFor($other);

        $response = $this->actingAs($user)->getJson('/api/v1/teams')->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame([$mine->id], $ids);
    }

    public function test_creating_a_team_makes_the_caller_its_owner_and_active_team(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/teams', ['name' => 'Acme Inc'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Acme Inc')
            ->assertJsonPath('data.member_count', 1);

        $teamId = $response->json('data.id');

        $this->assertDatabaseHas('teams', ['id' => $teamId, 'name' => 'Acme Inc', 'owner_id' => $user->id]);
        $this->assertDatabaseHas('team_memberships', ['team_id' => $teamId, 'user_id' => $user->id, 'role' => 'owner']);
        $this->assertSame($teamId, $user->fresh()->current_team_id);

        $this->actingAs($user)->getJson('/api/v1/team')->assertJsonPath('data.my_role', 'owner');
    }

    public function test_two_teams_with_the_same_name_get_distinct_slugs(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->actingAs($first)->postJson('/api/v1/teams', ['name' => 'Acme']);
        $this->actingAs($second)->postJson('/api/v1/teams', ['name' => 'Acme']);

        $slugs = Team::query()->pluck('slug')->all();

        $this->assertCount(2, array_unique($slugs));
    }

    public function test_only_owner_or_admin_can_rename_the_team(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $this->addMember($team, $member, 'member');

        $this->actingAs($member)
            ->patchJson("/api/v1/teams/{$team->id}", ['name' => 'New Name'])
            ->assertForbidden();

        $this->actingAs($owner)
            ->patchJson("/api/v1/teams/{$team->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_only_a_member_can_switch_to_that_team(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $team = $this->createTeamFor($owner);

        $this->actingAs($outsider)
            ->postJson("/api/v1/teams/{$team->id}/switch")
            ->assertStatus(422);

        $this->assertNull($outsider->fresh()->current_team_id);
    }

    public function test_only_owner_can_delete_the_team(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $this->addMember($team, $admin, 'admin');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/teams/{$team->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('teams', ['id' => $team->id]);

        $this->actingAs($owner)
            ->deleteJson("/api/v1/teams/{$team->id}")
            ->assertOk();

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
        $this->assertDatabaseMissing('team_memberships', ['team_id' => $team->id]);
    }

    private function createTeamFor(User $owner): Team
    {
        $team = Team::query()->create([
            'name' => 'Test Team',
            'slug' => 'test-team-'.$owner->id,
            'owner_id' => $owner->id,
        ]);

        $this->addMember($team, $owner, 'owner');
        $owner->forceFill(['current_team_id' => $team->id])->save();

        return $team;
    }

    private function addMember(Team $team, User $user, string $role): void
    {
        TeamMembership::query()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
        ]);
    }
}
