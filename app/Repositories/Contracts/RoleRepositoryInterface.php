<?php

namespace App\Repositories\Contracts;

use App\Data\Role\CreateRoleData;
use App\Data\Role\UpdateRoleData;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

interface RoleRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Role;

    public function findByName(string $name): ?Role;

    public function create(CreateRoleData $data): Role;

    public function update(Role $role, UpdateRoleData $data): Role;

    public function delete(Role $role): void;

    public function assignPermission(Role $role, string $permission): Role;

    public function revokePermission(Role $role, string $permission): Role;
}
