<?php

namespace App\Modules\Teams\Contracts;

use App\Models\User;
use App\Modules\Teams\Enums\TeamRole;
use App\Modules\Teams\Models\Team;
use App\Modules\Teams\Models\TeamInvite;
use App\Modules\Teams\Models\TeamMembership;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TeamServiceInterface
{
    public function create(User $owner, string $name): Team;

    /** The caller's membership row for $team, or null if they aren't a member — the shared authorization lookup every team controller needs. */
    public function membershipFor(Team $team, User $user): ?TeamMembership;

    public function rename(Team $team, string $name): Team;

    public function delete(Team $team): void;

    /** @throws \RuntimeException if $user is not a member of $team */
    public function switchCurrentTeam(User $user, Team $team): void;

    public function paginateMembers(
        Team $team,
        int $perPage = 15,
        ?string $search = null,
        ?string $sortBy = null,
        string $sortDirection = 'asc',
    ): LengthAwarePaginator;

    /** @throws \RuntimeException if $member is the team's owner, or $role is Owner */
    public function changeMemberRole(Team $team, User $member, TeamRole $role): TeamMembership;

    /** @throws \RuntimeException if $member is the team's owner */
    public function removeMember(Team $team, User $member): void;

    public function paginateInvites(Team $team, int $perPage = 15): LengthAwarePaginator;

    /** @throws \RuntimeException if $role is Owner, the email is already a member, or the team is at its member cap */
    public function createInvite(Team $team, User $inviter, string $email, TeamRole $role): TeamInvite;

    public function revokeInvite(Team $team, TeamInvite $invite): void;

    /**
     * @throws \RuntimeException if the token is unknown/expired/already accepted, the
     *                           authenticated user's email doesn't match the invite, or
     *                           the team is at its member cap
     */
    public function acceptInvite(string $token, User $user): TeamMembership;

    /** Platform-wide overview — every team regardless of the caller's membership. */
    public function paginateAllTeams(
        int $perPage = 15,
        ?string $search = null,
        ?string $sortBy = null,
        string $sortDirection = 'asc',
    ): LengthAwarePaginator;
}
