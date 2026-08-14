<?php

namespace App\Modules\Governance\Services;

use App\Contracts\UserAdminServiceInterface;
use App\Models\User;
use App\Modules\Governance\Enums\RoleChangeRequestStatus;
use App\Modules\Governance\Models\RoleChangeRequest;
use App\Services\UserAdminService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Decorates the real UserAdminService, swapped in only when Governance is enabled (see
 * GovernanceServiceProvider::register()). Intercepts create()/update() specifically when
 * the request would newly grant 'admin' or 'super-admin' — those roles are stripped
 * before delegating, and a pending RoleChangeRequest is filed instead of applying them
 * immediately. Every other method, and every non-elevation change, passes straight
 * through unchanged. This keeps UserAdminController/UserAdminService completely
 * unmodified and unaware Governance exists — the one-way module dependency discipline
 * already established for Billing/Teams.
 */
class ApprovalGatedUserAdminService implements UserAdminServiceInterface
{
    private const ELEVATED_ROLES = ['admin', 'super-admin'];

    public function __construct(
        private readonly UserAdminService $inner
    ) {}

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?string $role = null,
        ?string $status = null,
        ?string $sortBy = null,
        string $sortDirection = 'asc',
    ): LengthAwarePaginator {
        return $this->inner->paginate($perPage, $search, $role, $status, $sortBy, $sortDirection);
    }

    public function filtered(
        ?string $search = null,
        ?string $role = null,
        ?string $status = null,
        ?string $sortBy = null,
        string $sortDirection = 'asc',
    ): Collection {
        return $this->inner->filtered($search, $role, $status, $sortBy, $sortDirection);
    }

    public function create(array $data): User
    {
        $requestedRoles = $data['roles'] ?? null;
        $elevated = $this->elevatedRolesIn($requestedRoles, []);

        if ($elevated === []) {
            return $this->inner->create($data);
        }

        $data['roles'] = array_values(array_diff($requestedRoles, self::ELEVATED_ROLES));

        return DB::transaction(function () use ($data, $requestedRoles) {
            $user = $this->inner->create($data);

            $this->fileRoleChangeRequest($user, $requestedRoles, $user->getRoleNames()->all());

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        $requestedRoles = $data['roles'] ?? null;
        $currentRoles = $user->getRoleNames()->all();
        $elevated = $this->elevatedRolesIn($requestedRoles, $currentRoles);

        if ($elevated === []) {
            return $this->inner->update($user, $data);
        }

        $data['roles'] = array_values(array_diff($requestedRoles, self::ELEVATED_ROLES));

        return DB::transaction(function () use ($user, $data, $requestedRoles, $currentRoles) {
            $updated = $this->inner->update($user, $data);

            $this->fileRoleChangeRequest($updated, $requestedRoles, $currentRoles);

            return $updated;
        });
    }

    public function archive(User $user): User
    {
        return $this->inner->archive($user);
    }

    public function restore(User $user): User
    {
        return $this->inner->restore($user);
    }

    public function availableRoles(): array
    {
        return $this->inner->availableRoles();
    }

    public function availablePermissions(): array
    {
        return $this->inner->availablePermissions();
    }

    /** @return list<string> Elevated roles being newly requested that the user doesn't already hold. */
    private function elevatedRolesIn(?array $requestedRoles, array $currentRoles): array
    {
        if (! is_array($requestedRoles)) {
            return [];
        }

        return array_values(array_intersect(
            array_diff($requestedRoles, $currentRoles),
            self::ELEVATED_ROLES,
        ));
    }

    private function fileRoleChangeRequest(User $user, array $requestedRoles, array $previousRoles): void
    {
        RoleChangeRequest::query()->create([
            'user_id' => $user->id,
            'requested_roles' => array_values($requestedRoles),
            'previous_roles' => array_values($previousRoles),
            'requested_by' => auth()->id(),
            'status' => RoleChangeRequestStatus::Pending,
        ]);

        if (function_exists('activity')) {
            activity('governance')
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties(['requested_roles' => $requestedRoles])
                ->event('role_elevation_requested')
                ->log('Requested a role elevation pending approval');
        }
    }
}
