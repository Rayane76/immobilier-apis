<?php

namespace App\Repositories;

use App\Data\Property\CreatePropertyDTO;
use App\Data\Property\PropertyFilterDTO;
use App\Data\Property\UpdatePropertyDTO;
use App\Models\Property;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Optional;

class PropertyRepository implements PropertyRepositoryInterface
{
    // -------------------------------------------------------------------------
    // Common eager loads applied to every query that returns full models
    // -------------------------------------------------------------------------
    private const WITH = ['propertyType', 'region', 'rootRegion', 'countryRegion'];

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    public function paginate(PropertyFilterDTO $filter, ?int $ownedBy = null): LengthAwarePaginator
    {
        // All three cases (normal, trashed, unpublished) go through Meilisearch.
        //
        // Trashed:     scout.soft_delete=true keeps soft-deleted docs in the index
        //              with __soft_deleted=1; force-delete removes them entirely.
        //              We filter __soft_deleted=1 to browse trashed, __soft_deleted=0
        //              for everything else.
        //
        // Unpublished: created_by is indexed so agents can be scoped to their own
        //              unpublished listings via a created_by = {id} filter, while
        //              Super-Admin ($ownedBy = null) sees all.
        //
        // Do NOT use ->query() on the Scout builder: when a queryCallback is set,
        // Scout's getTotalCount() fires a second Meilisearch request to fetch *all*
        // matching IDs so it can re-count via Eloquent — with hundreds of documents
        // that second request times out and kills the RoadRunner worker.
        // Instead we call paginate() without ->query(), letting Scout hydrate models
        // via a single whereIn, then lazy-load relations on the hydrated collection.
        $meiliFilters = $this->buildMeilisearchFilters($filter, $ownedBy);
        $sort = $filter->trashed === true ? ['deleted_at:desc'] : ['created_at:desc'];

        $paginator = Property::search($filter->q ?? '', function ($engine, $query, $options) use ($meiliFilters, $sort) {
            $options['filter'] = $meiliFilters;
            $options['sort']   = $sort;
            return $engine->search($query, $options);
        })->paginate($filter->per_page);

        \Illuminate\Database\Eloquent\Collection::make($paginator->items())->loadMissing(self::WITH);

        return $paginator;
    }

    public function findById(int $id): ?Property
    {
        return Property::with(self::WITH)->find($id);
    }

    public function findByIdWithTrashed(int $id): ?Property
    {
        return Property::withTrashed()->with(self::WITH)->find($id);
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    public function create(CreatePropertyDTO $data, int $userId): Property
    {
        $property = Property::create([
            'property_type_id'  => $data->property_type_id,
            'listing_type'      => $data->listing_type,
            'title'             => $data->title ?? '',   // temporary – observer generates it
            'description'       => $data->description,
            'attributes'        => $data->attributes,
            'price'             => $data->price,
            'country_region_id' => $data->country_region_id,
            'root_region_id'    => $data->root_region_id,
            'region_id'         => $data->region_id,
            'address'           => $data->address,
            'is_published'      => $data->is_published ?? false,
            'published_at'      => $data->is_published ? ($data->published_at ?? now()) : null,
            'status'            => $data->status ?? Property::STATUS_AVAILABLE,
            'available_at'      => $data->available_at,
            'created_by'        => $userId,
        ]);

        return $property->load(self::WITH);
    }

    public function update(Property $property, UpdatePropertyDTO $data): Property
    {
        $payload = collect($data->toArray())
            ->reject(fn($v) => $v instanceof Optional)
            ->when(
                fn($c) => $c->has('is_published') && $c->get('is_published') === true && $property->published_at === null,
                fn($c) => $c->put('published_at', now())
            )
            ->toArray();

        $property->update($payload);

        return $property->load(self::WITH);
    }

    public function delete(Property $property, int $userId): void
    {
        $property->update(['deleted_by' => $userId]);
        $property->delete();
    }

    public function forceDelete(Property $property): void
    {
        $property->forceDelete();
    }

    public function restore(Property $property): void
    {
        $property->restore();
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * Build a Meilisearch filter string from the DTO.
     *
     * Relies on scout.soft_delete = true, which keeps soft-deleted documents in
     * the index tagged with __soft_deleted = 1.  Normal browsing therefore always
     * adds __soft_deleted = 0 and the trashed browse flips it to 1.  When browsing
     * trashed the is_published filter is intentionally omitted — it is not relevant
     * to deleted listings and skipping it avoids accidental mismatches.
     *
     * The optional $ownedBy parameter adds a created_by = {id} clause so that
     * agents are scoped to their own listings (unpublished or trashed) while
     * Super-Admin, which passes null, sees everything.
     *
     * Meilisearch filter syntax:
     *   field = value AND field >= value AND field <= value ...
     */
    private function buildMeilisearchFilters(PropertyFilterDTO $filter, ?int $ownedBy = null): string
    {
        $parts = [];

        // Soft-delete visibility — always present so the wrong bucket is never leaked.
        $parts[] = $filter->trashed === true ? '__soft_deleted = 1' : '__soft_deleted = 0';

        // Ownership scoping for agents browsing their own unpublished / trashed listings.
        // Super-Admin arrives with $ownedBy = null and therefore sees everything.
        if ($ownedBy !== null) {
            $parts[] = "created_by = {$ownedBy}";
        }

        // is_published is irrelevant (and potentially misleading) for deleted listings.
        if ($filter->is_published !== null && $filter->trashed !== true) {
            $parts[] = 'is_published = ' . ($filter->is_published ? 'true' : 'false');
        }
        if ($filter->listing_type !== null) {
            $parts[] = "listing_type = \"{$filter->listing_type}\"";
        }
        if ($filter->status !== null) {
            $parts[] = "status = \"{$filter->status}\"";
        }
        if ($filter->price_min !== null) {
            $parts[] = "price >= {$filter->price_min}";
        }
        if ($filter->price_max !== null) {
            $parts[] = "price <= {$filter->price_max}";
        }
        if ($filter->property_type_id !== null) {
            $parts[] = "property_type_id = {$filter->property_type_id}";
        }
        if ($filter->region_id !== null) {
            $parts[] = "region_id = {$filter->region_id}";
        }
        if ($filter->country_region_id !== null) {
            $parts[] = "country_region_id = {$filter->country_region_id}";
        }
        if ($filter->root_region_id !== null) {
            $parts[] = "root_region_id = {$filter->root_region_id}";
        }

        return implode(' AND ', $parts);
    }
}
