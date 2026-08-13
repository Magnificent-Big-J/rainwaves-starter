<?php

namespace App\Modules\Teams\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\Envelope;
use App\Modules\Teams\Contracts\TeamServiceInterface;
use App\Modules\Teams\Enums\TeamRole;
use App\Modules\Teams\Http\Requests\InviteTeamMemberRequest;
use App\Modules\Teams\Http\Resources\TeamInviteResource;
use App\Modules\Teams\Http\Resources\TeamMemberResource;
use App\Modules\Teams\Models\Team;
use App\Modules\Teams\Models\TeamInvite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TeamInviteController extends Controller
{
    public function __construct(
        private readonly TeamServiceInterface $teams
    ) {}

    public function index(Request $request, Team $team): JsonResponse
    {
        $this->authorizeManageMembers($request, $team);

        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));
        $invites = $this->teams->paginateInvites($team, $perPage);

        return Envelope::success(TeamInviteResource::collection($invites));
    }

    public function store(InviteTeamMemberRequest $request, Team $team): JsonResponse
    {
        $this->authorizeManageMembers($request, $team);

        try {
            $invite = $this->teams->createInvite(
                $team,
                $request->user(),
                $request->validated('email'),
                TeamRole::from($request->validated('role')),
            );
        } catch (RuntimeException $exception) {
            return Envelope::error($exception->getMessage(), [], 422);
        }

        return Envelope::success(new TeamInviteResource($invite->loadMissing('inviter')), 'Invitation sent.', [], 201);
    }

    public function destroy(Request $request, Team $team, TeamInvite $invite): JsonResponse
    {
        $this->authorizeManageMembers($request, $team);

        try {
            $this->teams->revokeInvite($team, $invite);
        } catch (RuntimeException $exception) {
            return Envelope::error($exception->getMessage(), [], 422);
        }

        return Envelope::success(null, 'Invitation revoked.');
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        try {
            $membership = $this->teams->acceptInvite($token, $request->user());
        } catch (RuntimeException $exception) {
            return Envelope::error($exception->getMessage(), [], 422);
        }

        return Envelope::success(new TeamMemberResource($membership->loadMissing(['user', 'team'])), 'Invitation accepted.');
    }

    private function authorizeManageMembers(Request $request, Team $team): void
    {
        $membership = $this->teams->membershipFor($team, $request->user());

        abort_unless($membership?->role->canManageMembers(), 403);
    }
}
