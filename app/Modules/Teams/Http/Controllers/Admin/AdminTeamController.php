<?php

namespace App\Modules\Teams\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\Envelope;
use App\Modules\Teams\Contracts\TeamServiceInterface;
use App\Modules\Teams\Http\Resources\TeamMemberResource;
use App\Modules\Teams\Http\Resources\TeamResource;
use App\Modules\Teams\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only platform overview (`can:teams.view`) — support/ops visibility into who's on
 * which team, deliberately with no write actions here. Self-service (TeamController etc.)
 * stays the only path that actually changes a team; see CLAUDE.md "Teams module".
 */
class AdminTeamController extends Controller
{
    public function __construct(
        private readonly TeamServiceInterface $teams
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));
        $search = $request->string('search')->toString() ?: null;
        $sortBy = $request->string('sort_by')->toString() ?: null;
        $sortDirection = $request->string('sort_direction')->toString() ?: 'asc';

        $teams = $this->teams->paginateAllTeams($perPage, $search, $sortBy, $sortDirection);

        return Envelope::success(TeamResource::collection($teams));
    }

    public function show(Team $team): JsonResponse
    {
        $team->loadMissing('owner')->loadCount('members');
        $members = $this->teams->paginateMembers($team, 100);

        return Envelope::success([
            'team' => new TeamResource($team),
            'members' => TeamMemberResource::collection($members),
        ]);
    }
}
