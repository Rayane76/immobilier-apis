<?php

namespace App\Services;

use App\Data\Role\AssignPermissionData;
use App\Data\Role\CreateRoleData;
use App\Data\Role\RoleData;
use App\Data\Role\UpdateRoleData;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->roleRepository->paginate($perPage)
            ->through(fn(Role $role) => RoleData::fromModel($role));
    }

    /**
     * Fetch the raw model — used by the controller to authorize before acting.
     */
    public function findModelOrFail(int $id): Role
    {
        $role = $this->roleRepository->findById($id);

        abort_unless($role, 404, 'Role not found.');

        return $role;
    }

    public function create(CreateRoleData $data): RoleData
    {
        return RoleData::fromModel(
            $this->roleRepository->create($data)
        );
    }

    /**
     * Model already fetched & authorized by the controller — no second lookup.
     */
    public function updateModel(Role $role, UpdateRoleData $data): RoleData
    {
        return RoleData::fromModel(
            $this->roleRepository->update($role, $data)
        );
    }

    /**
     * Model already fetched & authorized by the controller — no second lookup.
     */
    public function deleteModel(Role $role): void
    {
        $this->roleRepository->delete($role);
    }

    /**
     * Model already fetched & authorized by the controller — no second lookup.
     */
    public function assignPermission(Role $role, AssignPermissionData $data): RoleData
    {
        return RoleData::fromModel(
            $this->roleRepository->assignPermission($role, $data->permission)
        );
    }

    /**
     * Model already fetched & authorized by the controller — no second lookup.
     */
    public function revokePermission(Role $role, AssignPermissionData $data): RoleData
    {
        return RoleData::fromModel(
            $this->roleRepository->revokePermission($role, $data->permission)
        );
    }
}
