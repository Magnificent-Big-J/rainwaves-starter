<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\UserAdminServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Admin\UserAdminResource;
use App\Http\Responses\Envelope;
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

        return Envelope::success(UserAdminResource::collection($users), '', [
            'options' => [
                'roles' => $this->service->availableRoles(),
                'permissions' => $this->service->availablePermissions(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->service->create($request->validated());

        return Envelope::success(new UserAdminResource($user), 'User created.', [], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $updated = $this->service->update($user, $request->validated());

        return Envelope::success(new UserAdminResource($updated), 'User updated.');
    }
}
