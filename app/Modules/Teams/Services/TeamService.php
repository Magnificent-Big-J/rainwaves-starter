<?php

namespace App\Modules\Teams\Services;

use App\Models\User;
use App\Modules\Teams\Contracts\TeamServiceInterface;
use App\Modules\Teams\Enums\TeamRole;
use App\Modules\Teams\Models\Team;
use App\Modules\Teams\Models\TeamInvite;
use App\Modules\Teams\Models\TeamMembership;
use App\Modules\Teams\Notifications\TeamInvitationNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;

class TeamService implements TeamServiceInterface
{
    public function create(User $owner, string $name): Team
    {
        return DB::transaction(function () use ($owner, $name) {
            $team = Team::query()->create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'owner_id' => $owner->id,
            ]);

            TeamMembership::query()->create([
                'team_id' => $team->id,
                'user_id' => $owner->id,
                'role' => TeamRole::Owner,
                'joined_at' => now(),
            ]);

            $owner->forceFill(['current_team_id' => $team->id])->save();

            return $team;
        });
    }

    public function membershipFor(Team $team, User $user): ?TeamMembership
    {
        return TeamMembership::query()
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();
    }

    public function rename(Team $team, string $name): Team
    {
        $team->update(['name' => $name]);

        return $team->fresh();
    }

    public function delete(Team $team): void
    {
        $team->delete();
    }

    public function switchCurrentTeam(User $user, Team $team): void
    {
        $isMember = TeamMembership::query()
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $isMember) {
            throw new RuntimeException('You are not a member of this team.');
        }

        $user->forceFill(['current_team_id' => $team->id])->save();
    }

    public function paginateMembers(
        Team $team,
        int $perPage = 15,
        ?string $search = null,
        ?string $sortBy = null,
        string $sortDirection = 'asc',
    ): LengthAwarePaginator {
        $sortDirection = $sortDirection === 'desc' ? 'desc' : 'asc';

        $query = TeamMembership::query()
            ->with(['user', 'team'])
            ->where('team_id', $team->id);

        if ($search) {
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (in_array($sortBy, ['name', 'email'], true)) {
            $query->join('users', 'users.id', '=', 'team_memberships.user_id')
                ->orderBy("users.{$sortBy}", $sortDirection)
                ->select('team_memberships.*');
        } else {
            $query->orderBy('joined_at', $sortDirection);
        }

        return $query->paginate($perPage);
    }

    public function changeMemberRole(Team $team, User $member, TeamRole $role): TeamMembership
    {
        if ($member->id === $team->owner_id) {
            throw new RuntimeException("The team owner's role cannot be changed.");
        }

        if ($role === TeamRole::Owner) {
            throw new RuntimeException('Ownership cannot be granted through a role change.');
        }

        $membership = TeamMembership::query()
            ->where('team_id', $team->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        $membership->update(['role' => $role]);

        return $membership->fresh();
    }

    public function removeMember(Team $team, User $member): void
    {
        if ($member->id === $team->owner_id) {
            throw new RuntimeException('The team owner cannot be removed. Delete the team instead.');
        }

        TeamMembership::query()
            ->where('team_id', $team->id)
            ->where('user_id', $member->id)
            ->delete();

        if ($member->current_team_id === $team->id) {
            $member->forceFill(['current_team_id' => null])->save();
        }
    }

    public function paginateInvites(Team $team, int $perPage = 15): LengthAwarePaginator
    {
        return TeamInvite::query()
            ->with('inviter')
            ->where('team_id', $team->id)
            ->whereNull('accepted_at')
            ->latest()
            ->paginate($perPage);
    }

    public function createInvite(Team $team, User $inviter, string $email, TeamRole $role): TeamInvite
    {
        if ($role === TeamRole::Owner) {
            throw new RuntimeException('Ownership cannot be granted through an invite.');
        }

        return DB::transaction(function () use ($team, $inviter, $email, $role) {
            $alreadyMember = $team->members()->where('email', $email)->exists();

            if ($alreadyMember) {
                throw new RuntimeException('This person is already a member of the team.');
            }

            $this->assertUnderMemberCap($team);

            $invite = TeamInvite::query()->create([
                'team_id' => $team->id,
                'email' => $email,
                'role' => $role,
                'token' => Str::random(40),
                'invited_by' => $inviter->id,
                'expires_at' => now()->addDays((int) config('teams.invite_expiry_days', 7)),
            ]);

            Notification::route('mail', $email)->notify(new TeamInvitationNotification($invite, $team, $inviter));

            return $invite;
        });
    }

    public function revokeInvite(Team $team, TeamInvite $invite): void
    {
        if ($invite->team_id !== $team->id) {
            throw new RuntimeException('This invite does not belong to this team.');
        }

        $invite->delete();
    }

    public function acceptInvite(string $token, User $user): TeamMembership
    {
        return DB::transaction(function () use ($token, $user) {
            $invite = TeamInvite::query()->where('token', $token)->lockForUpdate()->first();

            if (! $invite || ! $invite->isPending()) {
                throw new RuntimeException('This invitation is no longer valid.');
            }

            if (strcasecmp($invite->email, $user->email) !== 0) {
                throw new RuntimeException('This invitation was sent to a different email address.');
            }

            $team = $invite->team;
            $this->assertUnderMemberCap($team);

            $membership = TeamMembership::query()->firstOrCreate(
                ['team_id' => $team->id, 'user_id' => $user->id],
                ['role' => $invite->role, 'joined_at' => now()]
            );

            $invite->forceFill(['accepted_at' => now()])->save();

            if (! $user->current_team_id) {
                $user->forceFill(['current_team_id' => $team->id])->save();
            }

            return $membership;
        });
    }

    public function paginateAllTeams(
        int $perPage = 15,
        ?string $search = null,
        ?string $sortBy = null,
        string $sortDirection = 'asc',
    ): LengthAwarePaginator {
        $allowedSorts = ['name', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'created_at';
        $sortDirection = $sortDirection === 'desc' ? 'desc' : 'asc';

        $query = Team::query()->with('owner')->withCount('members');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    private function assertUnderMemberCap(Team $team): void
    {
        $max = $team->maxMembers();
        $currentCount = $team->members()->count() + $team->invites()->whereNull('accepted_at')->count();

        if ($currentCount >= $max) {
            throw new RuntimeException("This team has reached its member limit of {$max}.");
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'team';
        $slug = $base;
        $suffix = 1;

        while (Team::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-".++$suffix;
        }

        return $slug;
    }
}
