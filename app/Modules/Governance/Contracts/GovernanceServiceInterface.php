<?php

namespace App\Modules\Governance\Contracts;

use App\Models\User;
use App\Modules\Governance\Models\RoleChangeRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface GovernanceServiceInterface
{
    /** @return array<string, mixed> Everything the caller's own account owns — see GovernanceService for the exact shape. */
    public function exportDataFor(User $user): array;

    /** @throws \RuntimeException if $user is the last remaining super-admin (same guard admin-side archiving uses) */
    public function deleteOwnAccount(User $user): void;

    public function paginatePendingRoleChangeRequests(int $perPage = 15): LengthAwarePaginator;

    /** @throws \RuntimeException if $approver requested the change themselves */
    public function approveRoleChangeRequest(RoleChangeRequest $roleChangeRequest, User $approver): RoleChangeRequest;

    /** @throws \RuntimeException if $approver requested the change themselves */
    public function rejectRoleChangeRequest(RoleChangeRequest $roleChangeRequest, User $approver): RoleChangeRequest;
}
