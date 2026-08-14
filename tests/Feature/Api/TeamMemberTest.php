<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Modules\Teams\Models\Team;
use App\Modules\Teams\Models\TeamMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMemberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_a_non_member_cannot_list_members(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $team = $this->createTeamFor($owner);

        $this->actingAs($outsider)
            ->getJson("/api/v1/teams/{$team->id}/members")
            ->assertForbidden();
    }

    public function test_a_member_can_list_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $this->addMember($team, $member, 'member');

        $this->actingAs($member)
            ->getJson("/api/v1/teams/{$team->id}/members")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_owner_can_change_a_members_role(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $this->addMember($team, $member, 'member');

        $this->actingAs($owner)
            ->patchJson("/api/v1/teams/{$team->id}/members/{$member->id}", ['role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('data.role', 'admin');
    }

    public function test_an_admin_cannot_change_their_own_role(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $this->addMember($team, $admin, 'admin');

        $this->actingAs($admin)
            ->patchJson("/api/v1/teams/{$team->id}/members/{$admin->id}", ['role' => 'member'])
            ->assertStatus(422);

        $this->assertDatabaseHas('team_memberships', ['team_id' => $team->id, 'user_id' => $admin->id, 'role' => 'admin']);
    }

    public function test_a_plain_member_cannot_change_roles(): void
    {
        $owner = User::factory()->create();
        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $this->addMember($team, $memberA, 'member');
        $this->addMember($team, $memberB, 'member');

        $this->actingAs($memberA)
            ->patchJson("/api/v1/teams/{$team->id}/members/{$memberB->id}", ['role' => 'admin'])
            ->assertForbidden();
    }

    public function test_the_owners_role_cannot_be_changed(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $this->addMember($team, $admin, 'admin');

        $this->actingAs($admin)
            ->patchJson("/api/v1/teams/{$team->id}/members/{$owner->id}", ['role' => 'member'])
            ->assertStatus(422);
    }

    public function test_ownership_cannot_be_granted_through_a_role_change(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $this->addMember($team, $member, 'member');

        $this->actingAs($owner)
            ->patchJson("/api/v1/teams/{$team->id}/members/{$member->id}", ['role' => 'owner'])
            ->assertStatus(422);
    }

    public function test_owner_or_admin_can_remove_a_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $this->addMember($team, $member, 'member');

        $this->actingAs($owner)
            ->deleteJson("/api/v1/teams/{$team->id}/members/{$member->id}")
            ->assertOk();

        $this->assertDatabaseMissing('team_memberships', ['team_id' => $team->id, 'user_id' => $member->id]);
    }

    public function test_the_owner_cannot_be_removed(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $this->addMember($team, $admin, 'admin');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/teams/{$team->id}/members/{$owner->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('team_memberships', ['team_id' => $team->id, 'user_id' => $owner->id]);
    }

    public function test_a_member_can_remove_themselves_and_it_clears_their_active_team(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $this->addMember($team, $member, 'member');
        $member->forceFill(['current_team_id' => $team->id])->save();

        $this->actingAs($member)
            ->deleteJson("/api/v1/teams/{$team->id}/members/{$member->id}")
            ->assertOk()
            ->assertJsonPath('message', 'You left the team.');

        $this->assertDatabaseMissing('team_memberships', ['team_id' => $team->id, 'user_id' => $member->id]);
        $this->assertNull($member->fresh()->current_team_id);
    }

    public function test_a_plain_member_cannot_remove_someone_else(): void
    {
        $owner = User::factory()->create();
        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $this->addMember($team, $memberA, 'member');
        $this->addMember($team, $memberB, 'member');

        $this->actingAs($memberA)
            ->deleteJson("/api/v1/teams/{$team->id}/members/{$memberB->id}")
            ->assertForbidden();
    }

    private function createTeamFor(User $owner): Team
    {
        $team = Team::query()->create([
            'name' => 'Test Team',
            'slug' => 'test-team-'.$owner->id,
            'owner_id' => $owner->id,
        ]);

        $this->addMember($team, $owner, 'owner');

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
