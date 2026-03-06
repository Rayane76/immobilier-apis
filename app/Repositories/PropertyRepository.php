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
        // Scout removes soft-deleted records from the Meilisearch index, so
        // browsing trashed properties must go through Eloquent directly.
        if ($filter->trashed === true) {
            return $this->browseTrashedWithEloquent($filter, $ownedBy);
        }

        // Browsing unpublished listings requires ownership scoping for agents,
        // so we bypass Meilisearch and use Eloquent directly.
        if ($filter->is_published === false) {
            return $this->browseUnpublishedWithEloquent($filter, $ownedBy);
        }

        $meiliFilters = $this->buildMeilisearchFilters($filter);

        // Do NOT use ->query() on the Scout builder:  when a queryCallback is
        // set, Scout's getTotalCount() fires a second Meilisearch request to
        // fetch *all* matching IDs so it can re-count via Eloquent — with
        // hundreds of documents that second request times out and kills the
        // RoadRunner worker.  Instead we call paginate() without ->query(),
        // letting Scout hydrate models via a single whereIn, then we
        // lazy-load the relations on the already-hydrated collection.
        $paginator = Property::search($filter->q ?? '', function ($engine, $query, $options) use ($meiliFilters) {
            if ($meiliFilters !== '') {
                $options['filter'] = $meiliFilters;
            }
            $options['sort'] = ['created_at:desc'];
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
     * Eloquent-only browse for soft-deleted properties.
     * Used when $filter->trashed === true because Scout removes deleted
     * records from the Meilisearch index and cannot serve them.
     */
    private function browseTrashedWithEloquent(PropertyFilterDTO $filter, ?int $ownedBy = null): LengthAwarePaginator
    {
        $query = Property::onlyTrashed()->with(self::WITH);

        // Scope to the caller's own records when they don't have unrestricted access.
        // Super-Admin reaches here with $ownedBy = null (Gate::before bypass, no
        // direct permission), so they see everything.
        if ($ownedBy !== null) {
            $query->where('created_by', $ownedBy);
        }

        if ($filter->listing_type !== null) {
            $query->where('listing_type', $filter->listing_type);
        }
        if ($filter->status !== null) {
            $query->where('status', $filter->status);
        }
        if ($filter->price_min !== null) {
            $query->where('price', '>=', $filter->price_min);
        }
        if ($filter->price_max !== null) {
            $query->where('price', '<=', $filter->price_max);
        }
        if ($filter->property_type_id !== null) {
            $query->where('property_type_id', $filter->property_type_id);
        }
        if ($filter->region_id !== null) {
            $query->where('region_id', $filter->region_id);
        }
        if ($filter->country_region_id !== null) {
            $query->where('country_region_id', $filter->country_region_id);
        }
        if ($filter->root_region_id !== null) {
            $query->where('root_region_id', $filter->root_region_id);
        }
        if (!empty($filter->q)) {
            $query->where(function ($q) use ($filter) {
                $q->where('title', 'ilike', "%{$filter->q}%")
                    ->orWhere('description', 'ilike', "%{$filter->q}%")
                    ->orWhere('address', 'ilike', "%{$filter->q}%");
            });
        }

        return $query->latest('deleted_at')->paginate($filter->per_page);
    }

    /**
     * Eloquent-only browse for unpublished properties.
     * Used when $filter->is_published === false because ownership scoping is
     * required for agents — they may only browse their own unpublished listings.
     * Super-Admin reaches here with $ownedBy = null (Gate::before bypass, no
     * direct permission), so they see everything.
     */
    private function browseUnpublishedWithEloquent(PropertyFilterDTO $filter, ?int $ownedBy = null): LengthAwarePaginator
    {
        $query = Property::where('is_published', false)->with(self::WITH);

        // Scope to the caller's own records when they don't have unrestricted access.
        if ($ownedBy !== null) {
            $query->where('created_by', $ownedBy);
        }

        if ($filter->listing_type !== null) {
            $query->where('listing_type', $filter->listing_type);
        }
        if ($filter->status !== null) {
            $query->where('status', $filter->status);
        }
        if ($filter->price_min !== null) {
            $query->where('price', '>=', $filter->price_min);
        }
        if ($filter->price_max !== null) {
            $query->where('price', '<=', $filter->price_max);
        }
        if ($filter->property_type_id !== null) {
            $query->where('property_type_id', $filter->property_type_id);
        }
        if ($filter->region_id !== null) {
            $query->where('region_id', $filter->region_id);
        }
        if ($filter->country_region_id !== null) {
            $query->where('country_region_id', $filter->country_region_id);
        }
        if ($filter->root_region_id !== null) {
            $query->where('root_region_id', $filter->root_region_id);
        }
        if (!empty($filter->q)) {
            $query->where(function ($q) use ($filter) {
                $q->where('title', 'ilike', "%{$filter->q}%")
                    ->orWhere('description', 'ilike', "%{$filter->q}%")
                    ->orWhere('address', 'ilike', "%{$filter->q}%");
            });
        }

        return $query->latest('created_at')->paginate($filter->per_page);
    }

    /**
     * Build a Meilisearch filter string from the DTO.
     *
     * Meilisearch filter syntax:
     *   field = value AND field >= value AND field <= value ...
     */
    private function buildMeilisearchFilters(PropertyFilterDTO $filter): string
    {
        $parts = [];

        if ($filter->is_published !== null) {
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
