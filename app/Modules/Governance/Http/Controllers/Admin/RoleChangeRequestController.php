<?php

namespace App\Modules\Governance\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\Envelope;
use App\Modules\Governance\Contracts\GovernanceServiceInterface;
use App\Modules\Governance\Http\Resources\RoleChangeRequestResource;
use App\Modules\Governance\Models\RoleChangeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class RoleChangeRequestController extends Controller
{
    public function __construct(
        private readonly GovernanceServiceInterface $governance
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        $requests = $this->governance->paginatePendingRoleChangeRequests($perPage);

        return Envelope::success(RoleChangeRequestResource::collection($requests));
    }

    public function approve(Request $request, RoleChangeRequest $roleChangeRequest): JsonResponse
    {
        try {
            $updated = $this->governance->approveRoleChangeRequest($roleChangeRequest, $request->user());
        } catch (RuntimeException $exception) {
            return Envelope::error($exception->getMessage(), [], 422);
        }

        return Envelope::success(new RoleChangeRequestResource($updated->loadMissing(['user', 'requester'])), 'Role change approved.');
    }

    public function reject(Request $request, RoleChangeRequest $roleChangeRequest): JsonResponse
    {
        try {
            $updated = $this->governance->rejectRoleChangeRequest($roleChangeRequest, $request->user());
        } catch (RuntimeException $exception) {
            return Envelope::error($exception->getMessage(), [], 422);
        }

        return Envelope::success(new RoleChangeRequestResource($updated->loadMissing(['user', 'requester'])), 'Role change rejected.');
    }
}
