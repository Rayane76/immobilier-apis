<?php

namespace App\Services;

use App\Data\Role\AssignPermissionDTO;
use App\Data\Role\CreateRoleDTO;
use App\Data\Role\RoleDTO;
use App\Data\Role\UpdateRoleDTO;
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
            ->through(fn(Role $role) => RoleDTO::fromModel($role));
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

    public function create(CreateRoleDTO $data): RoleDTO
    {
        return RoleDTO::fromModel(
            $this->roleRepository->create($data)
        );
    }

    /**
     * Model already fetched & authorized by the controller — no second lookup.
     */
    public function updateModel(Role $role, UpdateRoleDTO $data): RoleDTO
    {
        return RoleDTO::fromModel(
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
    public function assignPermission(Role $role, AssignPermissionDTO $data): RoleDTO
    {
        return RoleDTO::fromModel(
            $this->roleRepository->assignPermission($role, $data->permission)
        );
    }

    /**
     * Model already fetched & authorized by the controller — no second lookup.
     */
    public function revokePermission(Role $role, AssignPermissionDTO $data): RoleDTO
    {
        return RoleDTO::fromModel(
            $this->roleRepository->revokePermission($role, $data->permission)
        );
    }
}
