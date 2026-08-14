<?php

namespace App\Modules\Teams\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\Envelope;
use App\Models\User;
use App\Modules\Teams\Contracts\TeamServiceInterface;
use App\Modules\Teams\Enums\TeamRole;
use App\Modules\Teams\Http\Requests\UpdateTeamMemberRequest;
use App\Modules\Teams\Http\Resources\TeamMemberResource;
use App\Modules\Teams\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TeamMemberController extends Controller
{
    public function __construct(
        private readonly TeamServiceInterface $teams
    ) {}

    public function index(Request $request, Team $team): JsonResponse
    {
        $this->authorizeMember($request, $team);

        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));
        $search = $request->string('search')->toString() ?: null;
        $sortBy = $request->string('sort_by')->toString() ?: null;
        $sortDirection = $request->string('sort_direction')->toString() ?: 'asc';

        $members = $this->teams->paginateMembers($team, $perPage, $search, $sortBy, $sortDirection);

        return Envelope::success(TeamMemberResource::collection($members));
    }

    public function update(UpdateTeamMemberRequest $request, Team $team, User $user): JsonResponse
    {
        $this->authorizeManageMembers($request, $team);

        if ($user->getKey() === $request->user()->getKey()) {
            return Envelope::error('You cannot change your own role.', [], 422);
        }

        try {
            $membership = $this->teams->changeMemberRole($team, $user, TeamRole::from($request->validated('role')));
        } catch (RuntimeException $exception) {
            return Envelope::error($exception->getMessage(), [], 422);
        }

        return Envelope::success(new TeamMemberResource($membership->loadMissing(['user', 'team'])), 'Member role updated.');
    }

    public function destroy(Request $request, Team $team, User $user): JsonResponse
    {
        $isSelfRemoval = $user->getKey() === $request->user()->getKey();

        if (! $isSelfRemoval) {
            $this->authorizeManageMembers($request, $team);
        } elseif (! $this->teams->membershipFor($team, $request->user())) {
            abort(403);
        }

        try {
            $this->teams->removeMember($team, $user);
        } catch (RuntimeException $exception) {
            return Envelope::error($exception->getMessage(), [], 422);
        }

        return Envelope::success(null, $isSelfRemoval ? 'You left the team.' : 'Member removed.');
    }

    private function authorizeMember(Request $request, Team $team): void
    {
        abort_unless($this->teams->membershipFor($team, $request->user()) !== null, 403);
    }

    private function authorizeManageMembers(Request $request, Team $team): void
    {
        $membership = $this->teams->membershipFor($team, $request->user());

        abort_unless($membership?->role->canManageMembers(), 403);
    }
}
