<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\UserAdminServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Admin\UserAdminResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAdminController extends Controller
{
    public function __construct(
        private readonly UserAdminServiceInterface $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 10), 100));
        $search = $request->string('search')->toString() ?: null;
        $role = $request->string('role')->toString() ?: null;

        $users = $this->service->paginate($perPage, $search, $role);

        return response()->json([
            'data' => UserAdminResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'options' => [
                'roles' => $this->service->availableRoles(),
                'permissions' => $this->service->availablePermissions(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->service->create($request->validated());

        return response()->json(new UserAdminResource($user), 201);
    }

    public function update(UpdateUserRequest $request, User $user): UserAdminResource
    {
        $updated = $this->service->update($user, $request->validated());

        return new UserAdminResource($updated);
    }
}
