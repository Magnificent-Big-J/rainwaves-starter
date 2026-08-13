<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Modules\Teams\Models\Team;
use App\Modules\Teams\Models\TeamInvite;
use App\Modules\Teams\Models\TeamMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TeamInviteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Notification::fake();
    }

    public function test_owner_can_invite_a_new_member(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamFor($owner);

        $this->actingAs($owner)
            ->postJson("/api/v1/teams/{$team->id}/invites", ['email' => 'new@example.com', 'role' => 'member'])
            ->assertCreated()
            ->assertJsonPath('data.email', 'new@example.com')
            ->assertJsonPath('data.role', 'member');

        $this->assertDatabaseHas('team_invites', ['team_id' => $team->id, 'email' => 'new@example.com']);
    }

    public function test_a_plain_member_cannot_invite(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $this->addMember($team, $member, 'member');

        $this->actingAs($member)
            ->postJson("/api/v1/teams/{$team->id}/invites", ['email' => 'new@example.com', 'role' => 'member'])
            ->assertForbidden();
    }

    public function test_ownership_cannot_be_granted_through_an_invite(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamFor($owner);

        $this->actingAs($owner)
            ->postJson("/api/v1/teams/{$team->id}/invites", ['email' => 'new@example.com', 'role' => 'owner'])
            ->assertStatus(422);
    }

    public function test_cannot_invite_someone_who_is_already_a_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $this->addMember($team, $member, 'member');

        $this->actingAs($owner)
            ->postJson("/api/v1/teams/{$team->id}/invites", ['email' => $member->email, 'role' => 'member'])
            ->assertStatus(422);
    }

    public function test_invite_is_rejected_once_the_team_is_at_its_member_cap(): void
    {
        config(['teams.default_max_members' => 1]);

        $owner = User::factory()->create();
        $team = $this->createTeamFor($owner);

        $this->actingAs($owner)
            ->postJson("/api/v1/teams/{$team->id}/invites", ['email' => 'new@example.com', 'role' => 'member'])
            ->assertStatus(422);
    }

    public function test_pending_invites_count_toward_the_member_cap(): void
    {
        config(['teams.default_max_members' => 2]);

        $owner = User::factory()->create();
        $team = $this->createTeamFor($owner);

        $this->actingAs($owner)
            ->postJson("/api/v1/teams/{$team->id}/invites", ['email' => 'first@example.com', 'role' => 'member'])
            ->assertCreated();

        $this->actingAs($owner)
            ->postJson("/api/v1/teams/{$team->id}/invites", ['email' => 'second@example.com', 'role' => 'member'])
            ->assertStatus(422);
    }

    public function test_owner_can_revoke_a_pending_invite(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $invite = $this->createInvite($team, $owner, 'new@example.com');

        $this->actingAs($owner)
            ->deleteJson("/api/v1/teams/{$team->id}/invites/{$invite->id}")
            ->assertOk();

        $this->assertDatabaseMissing('team_invites', ['id' => $invite->id]);
    }

    public function test_accepting_a_valid_invite_creates_membership(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $invite = $this->createInvite($team, $owner, 'invitee@example.com');

        $this->actingAs($invitee)
            ->postJson("/api/v1/team-invites/{$invite->token}/accept")
            ->assertOk()
            ->assertJsonPath('data.role', 'member');

        $this->assertDatabaseHas('team_memberships', ['team_id' => $team->id, 'user_id' => $invitee->id, 'role' => 'member']);
        $this->assertNotNull($invite->fresh()->accepted_at);
        $this->assertSame($team->id, $invitee->fresh()->current_team_id);
    }

    public function test_accepting_with_a_mismatched_email_is_rejected(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $wrongPerson = User::factory()->create(['email' => 'someone-else@example.com']);
        $invite = $this->createInvite($team, $owner, 'invitee@example.com');

        $this->actingAs($wrongPerson)
            ->postJson("/api/v1/team-invites/{$invite->token}/accept")
            ->assertStatus(422);

        $this->assertDatabaseMissing('team_memberships', ['team_id' => $team->id, 'user_id' => $wrongPerson->id]);
    }

    public function test_accepting_an_expired_invite_is_rejected(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $invite = $this->createInvite($team, $owner, 'invitee@example.com', now()->subDay());

        $this->actingAs($invitee)
            ->postJson("/api/v1/team-invites/{$invite->token}/accept")
            ->assertStatus(422);
    }

    public function test_accepting_an_already_accepted_invite_is_rejected(): void
    {
        $owner = User::factory()->create();
        $team = $this->createTeamFor($owner);
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $invite = $this->createInvite($team, $owner, 'invitee@example.com');
        $invite->forceFill(['accepted_at' => now()])->save();

        $this->actingAs($invitee)
            ->postJson("/api/v1/team-invites/{$invite->token}/accept")
            ->assertStatus(422);
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

    private function createInvite(Team $team, User $inviter, string $email, $expiresAt = null): TeamInvite
    {
        return TeamInvite::query()->create([
            'team_id' => $team->id,
            'email' => $email,
            'role' => 'member',
            'token' => str()->random(40),
            'invited_by' => $inviter->id,
            'expires_at' => $expiresAt ?? now()->addDays(7),
        ]);
    }
}
