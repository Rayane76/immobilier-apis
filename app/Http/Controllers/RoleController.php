<?php

namespace App\Http\Controllers;

use App\Data\Role\AssignPermissionData;
use App\Data\Role\CreateRoleData;
use App\Data\Role\RoleData;
use App\Data\Role\UpdateRoleData;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

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

        return response()->json(RoleData::fromModel($role));
    }

    public function store(CreateRoleData $data): JsonResponse
    {
        $this->authorize('create', Role::class);

        return response()->json($this->roleService->create($data), 201);
    }

    public function update(UpdateRoleData $data, int $id): JsonResponse
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

    public function assignPermission(AssignPermissionData $data, int $id): JsonResponse
    {
        $role = $this->roleService->findModelOrFail($id);

        $this->authorize('assignPermission', $role);

        return response()->json($this->roleService->assignPermission($role, $data));
    }

    public function revokePermission(AssignPermissionData $data, int $id): JsonResponse
    {
        $role = $this->roleService->findModelOrFail($id);

        $this->authorize('revokePermission', $role);

        return response()->json($this->roleService->revokePermission($role, $data));
    }
}
