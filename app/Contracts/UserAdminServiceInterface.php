<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserAdminServiceInterface
{
    /**
     * @param  string|null  $status  'active' (default, excludes archived), 'archived', or 'all'
     * @param  string|null  $sortBy  one of: name, email, created_at
     */
    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?string $role = null,
        ?string $status = null,
        ?string $sortBy = null,
        string $sortDirection = 'asc',
    ): LengthAwarePaginator;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    /** @throws \RuntimeException if $user is the last remaining super-admin */
    public function archive(User $user): User;

    public function restore(User $user): User;

    public function availableRoles(): array;

    public function availablePermissions(): array;
}
