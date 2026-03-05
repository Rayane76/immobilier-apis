<?php

namespace App\Policies;

use App\Models\PropertyType;
use App\Models\User;

class PropertyTypePolicy
{
    // Super-Admin bypasses all methods via Gate::before in AppServiceProvider.

    public function viewAny(?User $user): bool
    {
        return true; // No permissions for PropertyType — all users can browse the listing.
    }

    public function view(?User $user, PropertyType $propertyType): bool
    {
        return true; // No permissions for PropertyType — all users can view a single property type.
    }

    public function create(User $user): bool
    {
        return $user->can('Create:PropertyType');
    }

    public function update(User $user, PropertyType $propertyType): bool
    {
        return $user->can('Update:PropertyType');
    }

    public function delete(User $user, PropertyType $propertyType): bool
    {
        return $user->can('Delete:PropertyType');
    }

    public function restore(User $user, PropertyType $propertyType): bool
    {
        return $user->can('Restore:PropertyType');
    }

    public function forceDelete(User $user, PropertyType $propertyType): bool
    {
        return $user->can('ForceDelete:PropertyType');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:PropertyType');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:PropertyType');
    }
}
