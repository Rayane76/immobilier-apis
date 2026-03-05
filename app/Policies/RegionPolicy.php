<?php

namespace App\Policies;

use App\Models\Region;
use App\Models\User;

class RegionPolicy
{
    // Super-Admin bypasses all methods via Gate::before in AppServiceProvider.

    public function viewAny(?User $user): bool
    {
        return true; // No permissions for Region — all users can browse the listing.
    }

    public function view(?User $user, Region $region): bool
    {
        return true; // No permissions for Region — all users can view a single region.
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Region');
    }

    public function update(User $user, Region $region): bool
    {
        return $user->can('Update:Region');
    }

    public function delete(User $user, Region $region): bool
    {
        return $user->can('Delete:Region');
    }

    public function restore(User $user, Region $region): bool
    {
        return $user->can('Restore:Region');
    }

    public function forceDelete(User $user, Region $region): bool
    {
        return $user->can('ForceDelete:Region');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Region');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Region');
    }
}
