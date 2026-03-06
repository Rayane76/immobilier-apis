<?php

namespace App\Repositories\Contracts;

use App\Data\Property\CreatePropertyDTO;
use App\Data\Property\PropertyFilterDTO;
use App\Data\Property\UpdatePropertyDTO;
use App\Models\Property;
use Illuminate\Pagination\LengthAwarePaginator;

interface PropertyRepositoryInterface
{
    /**
     * Return a paginated list of properties, optionally filtered / searched
     * via Meilisearch (when $filter->q is non-empty) or Eloquent.
     *
     * @param  int|null  $ownedBy  When set, trashed queries are scoped to this user ID.
     */
    public function paginate(PropertyFilterDTO $filter, ?int $ownedBy = null): LengthAwarePaginator;

    /**
     * Find a non-deleted property by its primary key.
     */
    public function findById(int $id): ?Property;

    /**
     * Find a property including soft-deleted rows (used for restore).
     */
    public function findByIdWithTrashed(int $id): ?Property;

    public function create(CreatePropertyDTO $data, int $userId): Property;

    public function update(Property $property, UpdatePropertyDTO $data): Property;

    /** Soft-delete and stamp deleted_by. */
    public function delete(Property $property, int $userId): void;

    /** Permanently remove the record. */
    public function forceDelete(Property $property): void;

    /** Restore a soft-deleted record. */
    public function restore(Property $property): void;
}
