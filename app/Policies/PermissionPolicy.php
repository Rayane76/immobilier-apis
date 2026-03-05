<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Permission');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can('View:Permission');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Permission');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->can('Update:Permission');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->can('Delete:Permission');
    }

    public function restore(User $user, Permission $permission): bool
    {
        return $user->can('Restore:Permission');
    }

    public function forceDelete(User $user, Permission $permission): bool
    {
        return $user->can('ForceDelete:Permission');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Permission');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Permission');
    }
}
