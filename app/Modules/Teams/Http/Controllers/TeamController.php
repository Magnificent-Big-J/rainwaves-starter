<?php

namespace App\Modules\Teams\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\Envelope;
use App\Modules\Teams\Contracts\TeamServiceInterface;
use App\Modules\Teams\Http\Requests\CreateTeamRequest;
use App\Modules\Teams\Http\Requests\UpdateTeamRequest;
use App\Modules\Teams\Http\Resources\TeamResource;
use App\Modules\Teams\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TeamController extends Controller
{
    public function __construct(
        private readonly TeamServiceInterface $teams
    ) {}

    /** Every team the caller belongs to — powers the sidebar team switcher. */
    public function index(Request $request): JsonResponse
    {
        return Envelope::success(TeamResource::collection($this->teams->teamsFor($request->user())));
    }

    /** The caller's active team — null data if they don't have one yet, not a 404. */
    public function show(Request $request): JsonResponse
    {
        $teamId = $request->user()->current_team_id;
        $team = $teamId ? Team::query()->with('owner')->withCount('members')->find($teamId) : null;
        $membership = $team ? $this->teams->membershipFor($team, $request->user()) : null;

        return Envelope::success([
            'team' => $team ? new TeamResource($team) : null,
            'my_role' => $membership?->role->value,
        ]);
    }

    public function store(CreateTeamRequest $request): JsonResponse
    {
        $team = $this->teams->create($request->user(), $request->validated('name'));
        $team->loadMissing('owner')->loadCount('members');

        return Envelope::success(new TeamResource($team), 'Team created.', [], 201);
    }

    public function update(UpdateTeamRequest $request, Team $team): JsonResponse
    {
        $this->authorizeManage($request, $team);

        $updated = $this->teams->rename($team, $request->validated('name'));
        $updated->loadMissing('owner')->loadCount('members');

        return Envelope::success(new TeamResource($updated), 'Team renamed.');
    }

    public function switch(Request $request, Team $team): JsonResponse
    {
        try {
            $this->teams->switchCurrentTeam($request->user(), $team);
        } catch (RuntimeException $exception) {
            return Envelope::error($exception->getMessage(), [], 422);
        }

        return Envelope::success(null, 'Active team switched.');
    }

    public function destroy(Request $request, Team $team): JsonResponse
    {
        if ($team->owner_id !== $request->user()->getKey()) {
            return Envelope::error('Only the team owner can delete this team.', [], 403);
        }

        $this->teams->delete($team);

        return Envelope::success(null, 'Team deleted.');
    }

    private function authorizeManage(Request $request, Team $team): void
    {
        $membership = $this->teams->membershipFor($team, $request->user());

        abort_unless($membership?->role->canManageTeam(), 403);
    }
}
