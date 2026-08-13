<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\UserAdminServiceInterface;
use App\Exports\CollectionExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Admin\UserAdminResource;
use App\Http\Responses\Envelope;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        $status = $request->string('status')->toString() ?: null;
        $sortBy = $request->string('sort_by')->toString() ?: null;
        $sortDirection = $request->string('sort_direction')->toString() ?: 'asc';

        $users = $this->service->paginate($perPage, $search, $role, $status, $sortBy, $sortDirection);

        return Envelope::success(UserAdminResource::collection($users), '', [
            'options' => [
                'roles' => $this->service->availableRoles(),
                'permissions' => $this->service->availablePermissions(),
            ],
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $search = $request->string('search')->toString() ?: null;
        $role = $request->string('role')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;
        $sortBy = $request->string('sort_by')->toString() ?: null;
        $sortDirection = $request->string('sort_direction')->toString() ?: 'asc';

        $users = $this->service->filtered($search, $role, $status, $sortBy, $sortDirection);

        return Excel::download(
            new CollectionExport(
                $users,
                ['Name', 'Email', 'Roles', 'Status', 'Joined'],
                fn (User $user) => [
                    $user->name,
                    $user->email,
                    $user->roles->pluck('name')->implode(', '),
                    $user->trashed() ? 'Archived' : 'Active',
                    $user->created_at?->toDateString(),
                ],
            ),
            'users.xlsx',
        );
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

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            return Envelope::error('You cannot archive your own account.', [], 422);
        }

        try {
            $archived = $this->service->archive($user);
        } catch (RuntimeException $exception) {
            return Envelope::error($exception->getMessage(), [], 422);
        }

        return Envelope::success(new UserAdminResource($archived), 'User archived.');
    }

    public function restore(User $user): JsonResponse
    {
        $restored = $this->service->restore($user);

        return Envelope::success(new UserAdminResource($restored), 'User restored.');
    }
}
