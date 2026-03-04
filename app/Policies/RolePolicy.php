<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Role');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('View:Role');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Role');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('Update:Role');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('Delete:Role');
    }

    public function restore(User $user, Role $role): bool
    {
        return $user->can('Restore:Role');
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return $user->can('ForceDelete:Role');
    }

    /**
     * Determine whether the user can assign a permission to a given role.
     * Controls the role_has_permissions pivot.
     */
    public function assignPermission(User $user, Role $role): bool
    {
        return $user->can('AssignPermission:Role');
    }

    /**
     * Determine whether the user can revoke a permission from a given role.
     * Controls the role_has_permissions pivot.
     */
    public function revokePermission(User $user, Role $role): bool
    {
        return $user->can('RevokePermission:Role');
    }
}
