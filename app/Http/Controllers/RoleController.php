<?php

namespace App\Http\Controllers;

use App\Data\Role\AssignPermissionDTO;
use App\Data\Role\CreateRoleDTO;
use App\Data\Role\RoleDTO;
use App\Data\Role\UpdateRoleDTO;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

/**
 * @group Roles
 *
 * Manage roles and their associated permissions. All endpoints require authentication.
 */
class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return response()->json($this->roleService->paginate());
    }

    public function show(int $id): JsonResponse
    {
        $role = $this->roleService->findModelOrFail($id);

        $this->authorize('view', $role);

        return response()->json(RoleDTO::fromModel($role));
    }

    public function store(CreateRoleDTO $data): JsonResponse
    {
        $this->authorize('create', Role::class);

        return response()->json($this->roleService->create($data), 201);
    }

    public function update(UpdateRoleDTO $data, int $id): JsonResponse
    {
        $role = $this->roleService->findModelOrFail($id);

        $this->authorize('update', $role);

        return response()->json($this->roleService->updateModel($role, $data));
    }

    public function destroy(int $id): JsonResponse
    {
        $role = $this->roleService->findModelOrFail($id);

        $this->authorize('delete', $role);

        $this->roleService->deleteModel($role);

        return response()->json(null, 204);
    }

    public function assignPermission(AssignPermissionDTO $data, int $id): JsonResponse
    {
        $role = $this->roleService->findModelOrFail($id);

        $this->authorize('assignPermission', $role);

        return response()->json($this->roleService->assignPermission($role, $data));
    }

    public function revokePermission(AssignPermissionDTO $data, int $id): JsonResponse
    {
        $role = $this->roleService->findModelOrFail($id);

        $this->authorize('revokePermission', $role);

        return response()->json($this->roleService->revokePermission($role, $data));
    }
}
