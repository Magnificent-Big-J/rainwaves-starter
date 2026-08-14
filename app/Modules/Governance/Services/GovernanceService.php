<?php

namespace App\Modules\Governance\Services;

use App\Contracts\UserAdminServiceInterface;
use App\Models\User;
use App\Modules\Billing\Models\Payment;
use App\Modules\Governance\Contracts\GovernanceServiceInterface;
use App\Modules\Governance\Enums\RoleChangeRequestStatus;
use App\Modules\Governance\Models\RoleChangeRequest;
use App\Modules\Teams\Models\TeamMembership;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

class GovernanceService implements GovernanceServiceInterface
{
    public function __construct(
        private readonly UserAdminServiceInterface $userAdmin
    ) {}

    public function exportDataFor(User $user): array
    {
        $data = [
            'exported_at' => now()->toIso8601String(),
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->all(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ];

        // Optional data providers — guarded by whether the owning module is actually
        // enabled, not a hard Governance dependency on Teams/Billing (see
        // GovernanceModule::dependencies()): this export degrades gracefully rather
        // than requiring either module to be present.
        if (config('modules.teams') && class_exists(TeamMembership::class)) {
            $data['team_memberships'] = TeamMembership::query()
                ->where('user_id', $user->id)
                ->with('team:id,name,slug')
                ->get()
                ->map(fn (TeamMembership $membership) => [
                    'team' => $membership->team?->name,
                    'role' => $membership->role instanceof \BackedEnum ? $membership->role->value : $membership->role,
                    'joined_at' => $membership->joined_at?->toIso8601String(),
                ])
                ->all();
        }

        if (config('modules.billing') && class_exists(Payment::class)) {
            $data['payments'] = Payment::query()
                ->where('user_id', $user->id)
                ->get(['id', 'status', 'amount_gross', 'created_at'])
                ->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'status' => $payment->status instanceof \BackedEnum ? $payment->status->value : $payment->status,
                    'amount' => $payment->amount_gross,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ])
                ->all();
        }

        if (class_exists(Activity::class)) {
            $data['activity'] = Activity::query()
                ->where('causer_type', User::class)
                ->where('causer_id', $user->id)
                ->latest()
                ->limit(500)
                ->get(['log_name', 'description', 'event', 'created_at'])
                ->map(fn (Activity $entry) => [
                    'log_name' => $entry->log_name,
                    'description' => $entry->description,
                    'event' => $entry->event,
                    'created_at' => $entry->created_at?->toIso8601String(),
                ])
                ->all();
        }

        if (function_exists('activity')) {
            activity('governance')
                ->performedOn($user)
                ->causedBy($user)
                ->event('data_exported')
                ->log('Exported own data');
        }

        return $data;
    }

    public function deleteOwnAccount(User $user): void
    {
        // Reuses the exact method admin-side archiving already uses — the "last
        // super-admin cannot be archived" guard applies for free, no duplicated rule.
        $this->userAdmin->archive($user);

        if (function_exists('activity')) {
            activity('governance')
                ->performedOn($user)
                ->causedBy($user)
                ->event('account_self_deleted')
                ->log('Self-deleted account');
        }
    }

    public function paginatePendingRoleChangeRequests(int $perPage = 15): LengthAwarePaginator
    {
        return RoleChangeRequest::query()
            ->where('status', RoleChangeRequestStatus::Pending)
            ->with(['user:id,name,email', 'requester:id,name,email'])
            ->latest()
            ->paginate($perPage);
    }

    public function approveRoleChangeRequest(RoleChangeRequest $roleChangeRequest, User $approver): RoleChangeRequest
    {
        return DB::transaction(function () use ($roleChangeRequest, $approver) {
            $this->assertApprovable($roleChangeRequest, $approver);

            $target = $roleChangeRequest->user;
            $target->syncRoles($roleChangeRequest->requested_roles);

            $roleChangeRequest->forceFill([
                'status' => RoleChangeRequestStatus::Approved,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ])->save();

            if (function_exists('activity')) {
                activity('governance')
                    ->performedOn($target)
                    ->causedBy($approver)
                    ->withProperties(['requested_roles' => $roleChangeRequest->requested_roles])
                    ->event('role_elevation_approved')
                    ->log('Approved a role elevation request');
            }

            return $roleChangeRequest->fresh();
        });
    }

    public function rejectRoleChangeRequest(RoleChangeRequest $roleChangeRequest, User $approver): RoleChangeRequest
    {
        return DB::transaction(function () use ($roleChangeRequest, $approver) {
            $this->assertApprovable($roleChangeRequest, $approver);

            $roleChangeRequest->forceFill([
                'status' => RoleChangeRequestStatus::Rejected,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ])->save();

            if (function_exists('activity')) {
                activity('governance')
                    ->performedOn($roleChangeRequest->user)
                    ->causedBy($approver)
                    ->withProperties(['requested_roles' => $roleChangeRequest->requested_roles])
                    ->event('role_elevation_rejected')
                    ->log('Rejected a role elevation request');
            }

            return $roleChangeRequest->fresh();
        });
    }

    private function assertApprovable(RoleChangeRequest $roleChangeRequest, User $approver): void
    {
        if (! $roleChangeRequest->isPending()) {
            throw new RuntimeException('This request has already been decided.');
        }

        if ($roleChangeRequest->requested_by === $approver->id) {
            throw new RuntimeException('You cannot approve a role change you requested yourself.');
        }
    }
}
