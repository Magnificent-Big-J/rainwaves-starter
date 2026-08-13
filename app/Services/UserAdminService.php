<?php

namespace App\Services;

use App\Contracts\UserAdminServiceInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserAdminService implements UserAdminServiceInterface
{
    private const SORTABLE_COLUMNS = ['name', 'email', 'created_at'];

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?string $role = null,
        ?string $status = null,
        ?string $sortBy = null,
        string $sortDirection = 'asc',
    ): LengthAwarePaginator {
        $query = match ($status) {
            'archived' => User::onlyTrashed(),
            'all' => User::withTrashed(),
            default => User::query(),
        };

        return $query
            ->when($search, function ($query, $searchTerm) {
                $query->where(function ($inner) use ($searchTerm) {
                    $inner->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                });
            })
            ->when($role, function ($query, $roleName) {
                $normalizedRole = Str::lower(trim((string) $roleName));

                $query->whereHas('roles', function ($rolesQuery) use ($normalizedRole) {
                    $rolesQuery->whereRaw('LOWER(name) = ?', [$normalizedRole]);
                });
            })
            ->when(
                in_array($sortBy, self::SORTABLE_COLUMNS, true),
                fn ($query) => $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc'),
                fn ($query) => $query->latest('id'),
            )
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $roles = Arr::pull($data, 'roles', []);
            $permissions = Arr::pull($data, 'permissions', []);

            $user = User::query()->create($data);

            if ($this->permissionTablesReady() && method_exists($user, 'syncRoles')) {
                $user->syncRoles($roles);
            }

            if ($this->permissionTablesReady() && method_exists($user, 'syncPermissions')) {
                $user->syncPermissions($permissions);
            }

            if (function_exists('activity')) {
                activity('admin-users')
                    ->performedOn($user)
                    ->causedBy(auth()->user())
                    ->withProperties(['roles' => $roles, 'permissions' => $permissions])
                    ->event('created')
                    ->log('Admin created user');
            }

            return $user->refresh();
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $roles = Arr::pull($data, 'roles', null);
            $permissions = Arr::pull($data, 'permissions', null);
            $password = Arr::pull($data, 'password');
            Arr::pull($data, 'password_confirmation');

            if (is_string($password) && trim($password) !== '') {
                $data['password'] = $password;
            }

            $user->fill($data);
            $user->save();

            if ($this->permissionTablesReady() && is_array($roles) && method_exists($user, 'syncRoles')) {
                $user->syncRoles($roles);
            }

            if ($this->permissionTablesReady() && is_array($permissions) && method_exists($user, 'syncPermissions')) {
                $user->syncPermissions($permissions);
            }

            if (function_exists('activity')) {
                activity('admin-users')
                    ->performedOn($user)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'roles' => $roles,
                        'permissions' => $permissions,
                        'changes' => array_keys($data),
                    ])
                    ->event('updated')
                    ->log('Admin updated user');
            }

            return $user->refresh();
        });
    }

    public function archive(User $user): User
    {
        return DB::transaction(function () use ($user) {
            if ($user->hasRole('super-admin') && $this->isLastSuperAdmin($user)) {
                throw new RuntimeException('Cannot archive the last remaining super-admin — promote another user first.');
            }

            $user->delete();

            if (function_exists('activity')) {
                activity('admin-users')
                    ->performedOn($user)
                    ->causedBy(auth()->user())
                    ->event('archived')
                    ->log('Admin archived user');
            }

            return $user;
        });
    }

    public function restore(User $user): User
    {
        return DB::transaction(function () use ($user) {
            $user->restore();

            if (function_exists('activity')) {
                activity('admin-users')
                    ->performedOn($user)
                    ->causedBy(auth()->user())
                    ->event('restored')
                    ->log('Admin restored user');
            }

            return $user->refresh();
        });
    }

    private function isLastSuperAdmin(User $user): bool
    {
        if (! $this->permissionTablesReady()) {
            return false;
        }

        return User::role('super-admin')->where('id', '!=', $user->id)->doesntExist();
    }

    public function availableRoles(): array
    {
        if (! class_exists(Role::class) || ! Schema::hasTable('roles')) {
            return [];
        }

        return Role::query()->orderBy('name')->pluck('name')->all();
    }

    public function availablePermissions(): array
    {
        if (! class_exists(Permission::class) || ! Schema::hasTable('permissions')) {
            return [];
        }

        return Permission::query()->orderBy('name')->pluck('name')->all();
    }

    private function permissionTablesReady(): bool
    {
        return Schema::hasTable('roles')
            && Schema::hasTable('permissions')
            && Schema::hasTable('model_has_roles')
            && Schema::hasTable('model_has_permissions')
            && Schema::hasTable('role_has_permissions');
    }
}
