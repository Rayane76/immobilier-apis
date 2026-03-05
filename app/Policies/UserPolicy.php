<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:User');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('View:User');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:User');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('Update:User');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('Delete:User');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('Restore:User');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->can('ForceDelete:User');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:User');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:User');
    }

    /**
     * Determine whether the user can assign a role to a given user.
     * Controls the model_has_roles pivot.
     */
    public function assignRole(User $user, User $model): bool
    {
        return $user->can('AssignRole:User');
    }

    /**
     * Determine whether the user can revoke a role from a given user.
     * Controls the model_has_roles pivot.
     */
    public function revokeRole(User $user, User $model): bool
    {
        return $user->can('RevokeRole:User');
    }
}
