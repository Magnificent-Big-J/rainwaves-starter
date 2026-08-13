<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use App\Http\Resources\Admin\RoleAdminResource;
use App\Http\Responses\Envelope;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAdminController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::query()->with('permissions')->orderBy('name')->get();

        return Envelope::success(RoleAdminResource::collection($this->withUserCounts($roles)), '', [
            'options' => [
                'permissions' => Permission::query()->orderBy('name')->pluck('name')->all(),
            ],
        ]);
    }

    public function updatePermissions(UpdateRolePermissionsRequest $request, Role $role): JsonResponse
    {
        if ($role->name === 'super-admin') {
            return Envelope::error("The super-admin role's permissions are fixed and cannot be changed here.", [], 422);
        }

        $role->syncPermissions($request->validated('permissions'));

        $updated = $this->withUserCounts(new Collection([$role->load('permissions')]))->first();

        return Envelope::success(new RoleAdminResource($updated), 'Role permissions updated.');
    }

    /**
     * Deliberately not Role::withCount('users'): that relation resolves its guard via
     * config('auth.defaults.guard') on a *fresh* (attribute-less) Role instance, and
     * Illuminate\Auth\Middleware\Authenticate::authenticate() calls
     * AuthManager::shouldUse('sanctum') for every auth:sanctum request — mutating that
     * same config default guard-wide. 'sanctum' has no config('auth.guards.sanctum')
     * entry (it's registered programmatically), so getModelForGuard() returns null and
     * the relation build fatals. Counting the pivot table directly sidesteps guard
     * resolution entirely.
     */
    private function withUserCounts(Collection $roles): Collection
    {
        $counts = DB::table(config('permission.table_names.model_has_roles'))
            ->where('model_type', User::class)
            ->select('role_id', DB::raw('count(*) as aggregate'))
            ->groupBy('role_id')
            ->pluck('aggregate', 'role_id');

        return $roles->each(fn (Role $role) => $role->setAttribute('users_count', (int) $counts->get($role->id, 0)));
    }
}
