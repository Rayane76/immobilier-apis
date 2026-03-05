<?php

namespace App\Policies;

use App\Models\Attribute;
use App\Models\User;

class AttributePolicy
{
    // Super-Admin bypasses all methods via Gate::before in AppServiceProvider.

    public function viewAny(?User $user): bool
    {
        return true; // No permissions for Attribute — all users can browse the listing.
    }

    public function view(?User $user, Attribute $attribute): bool
    {
        return true; // No permissions for Attribute — all users can view a single attribute.
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Attribute');
    }

    public function update(User $user, Attribute $attribute): bool
    {
        return $user->can('Update:Attribute');
    }

    public function delete(User $user, Attribute $attribute): bool
    {
        return $user->can('Delete:Attribute');
    }

    public function restore(User $user, Attribute $attribute): bool
    {
        return $user->can('Restore:Attribute');
    }

    public function forceDelete(User $user, Attribute $attribute): bool
    {
        return $user->can('ForceDelete:Attribute');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Attribute');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Attribute');
    }
}
