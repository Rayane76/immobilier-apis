<?php

namespace App\Http\Controllers;

use App\Data\User\AssignRoleDTO;
use App\Data\User\CreateUserDTO;
use App\Data\User\UpdateUserDTO;
use App\Data\UserDTO;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

/**
 * @group Users
 *
 * Manage users, their roles, and profile data. All endpoints require authentication.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return response()->json($this->userService->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findModelOrFail($id);

        $this->authorize('view', $user);

        return response()->json(UserDTO::fromModel($user));
    }

    public function store(CreateUserDTO $data): JsonResponse
    {
        $this->authorize('create', User::class);

        return response()->json($this->userService->create($data), 201);
    }

    public function update(UpdateUserDTO $data, int $id): JsonResponse
    {
        $user = $this->userService->findModelOrFail($id);

        $this->authorize('update', $user);

        return response()->json($this->userService->updateModel($user, $data));
    }

    public function destroy(int $id): JsonResponse
    {
        $user = $this->userService->findModelOrFail($id);

        $this->authorize('delete', $user);

        $this->userService->deleteModel($user);

        return response()->json(null, 204);
    }

    public function assignRole(AssignRoleDTO $data, int $id): JsonResponse
    {
        $user = $this->userService->findModelOrFail($id);

        $this->authorize('assignRole', $user);

        return response()->json($this->userService->assignRole($user, $data));
    }

    public function revokeRole(AssignRoleDTO $data, int $id): JsonResponse
    {
        $user = $this->userService->findModelOrFail($id);

        $this->authorize('revokeRole', $user);

        return response()->json($this->userService->revokeRole($user, $data));
    }
}
