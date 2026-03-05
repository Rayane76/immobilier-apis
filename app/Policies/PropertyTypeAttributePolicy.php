<?php

namespace App\Policies;

use App\Models\PropertyTypeAttribute;
use App\Models\User;

class PropertyTypeAttributePolicy
{
    // All abilities are permission-driven. Visiteurs have no PropertyTypeAttribute permissions.
    // Super-Admin bypasses all methods via Gate::before in AppServiceProvider.
    //
    // No restore/forceDelete — PropertyTypeAttribute has no SoftDeletes.
    // When using BelongsToMany helpers (attach/sync/detach) on a controller,
    // authorise against the parent PropertyType (e.g. `update`) instead of this policy.

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:PropertyTypeAttribute');
    }

    public function view(User $user, PropertyTypeAttribute $propertyTypeAttribute): bool
    {
        return $user->can('View:PropertyTypeAttribute');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:PropertyTypeAttribute');
    }

    public function update(User $user, PropertyTypeAttribute $propertyTypeAttribute): bool
    {
        return $user->can('Update:PropertyTypeAttribute');
    }

    public function delete(User $user, PropertyTypeAttribute $propertyTypeAttribute): bool
    {
        return $user->can('Delete:PropertyTypeAttribute');
    }
}
