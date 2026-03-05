<?php

namespace App\Repositories;

use App\Data\Role\CreateRoleData;
use App\Data\Role\UpdateRoleData;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Optional;
use Spatie\Permission\Models\Role;

class RoleRepository implements RoleRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Role::with('permissions')->paginate($perPage);
    }

    public function findById(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    public function findByName(string $name): ?Role
    {
        return Role::with('permissions')->where('name', $name)->first();
    }

    public function create(CreateRoleData $data): Role
    {
        $role = Role::create(['name' => $data->name, 'guard_name' => 'web']);

        return $role->load('permissions');
    }

    public function update(Role $role, UpdateRoleData $data): Role
    {
        $payload = collect($data->toArray())
            ->reject(fn($v) => $v instanceof Optional)
            ->toArray();

        $role->update($payload);

        return $role->load('permissions');
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    public function assignPermission(Role $role, string $permission): Role
    {
        $role->givePermissionTo($permission);

        return $role->load('permissions');
    }

    public function revokePermission(Role $role, string $permission): Role
    {
        $role->revokePermissionTo($permission);

        return $role->load('permissions');
    }
}
