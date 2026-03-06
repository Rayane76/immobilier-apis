<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    // Visiteurs hold ViewAny:Property + View:Property — granted in the seeder.
    // All other authenticated roles hold the full set.
    // Ownership (created_by) is enforced on write operations at the instance level.
    // Super-Admin bypasses all methods via Gate::before in AppServiceProvider.

    /**
     * Anyone — including unauthenticated guests — can browse published listings.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Anyone — including unauthenticated guests — can view a published, non-deleted property.
     * Deleted properties are gated separately via viewDeleted().
     * Unpublished properties are gated separately via viewUnpublished().
     */
    public function view(?User $user, Property $property): bool
    {
        return true;
    }

    /**
     * View a single unpublished property.
     *
     * Agents may only view their own unpublished listings (e.g. drafts they are
     * still editing). Super-Admin bypasses this via Gate::before.
     */
    public function viewUnpublished(User $user, Property $property): bool
    {
        return $user->can('ViewUnpublished:Property')
            && $property->created_by === $user->id;
    }

    /**
     * Browse unpublished listings (index with ?is_published=false).
     *
     * Both agents and Super-Admin pass this check. The repository then scopes
     * the results to created_by = user->id for agents (hasDirectPermission),
     * while Super-Admin (granted via Gate::before, not directly) sees everything.
     */
    public function viewAnyUnpublished(User $user): bool
    {
        return $user->can('ViewAnyUnpublished:Property');
    }

    /**
     * View a single soft-deleted property.
     *
     * Agents may only view their own deleted listings so they can decide
     * whether to restore them. Super-Admin bypasses this via Gate::before.
     */
    public function viewDeleted(User $user, Property $property): bool
    {
        return $user->can('ViewDeleted:Property')
            && $property->created_by === $user->id;
    }

    /**
     * Browse the trashed listing (index with ?trashed=true).
     *
     * Both agents and Super-Admin pass this check. The repository then scopes
     * the results to created_by = user->id for agents (hasDirectPermission),
     * while Super-Admin (granted via Gate::before, not directly) sees everything.
     */
    public function viewAnyDeleted(User $user): bool
    {
        return $user->can('ViewAnyDeleted:Property');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Property');
    }

    public function update(User $user, Property $property): bool
    {
        return $user->can('Update:Property')
            && $property->created_by === $user->id;
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->can('Delete:Property')
            && $property->created_by === $user->id;
    }

    public function restore(User $user, Property $property): bool
    {
        return $user->can('Restore:Property')
            && $property->created_by === $user->id;
    }

    public function forceDelete(User $user, Property $property): bool
    {
        return $user->can('ForceDelete:Property')
            && $property->created_by === $user->id;
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Property');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Property');
    }
}
