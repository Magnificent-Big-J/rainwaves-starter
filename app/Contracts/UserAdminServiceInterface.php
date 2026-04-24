<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserAdminServiceInterface
{
    public function paginate(int $perPage = 15, ?string $search = null, ?string $role = null): LengthAwarePaginator;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function availableRoles(): array;

    public function availablePermissions(): array;
}
