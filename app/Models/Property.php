<?php

namespace App\Models;

use App\Support\PropertyAttributeHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Property extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\PropertyFactory> */
    use HasFactory, SoftDeletes, InteractsWithMedia, Searchable;

    // -------------------------------------------------------------------------
    // Enum constants (mirror the DB enums for type-safe usage)
    // -------------------------------------------------------------------------
    const LISTING_SALE = 'sale';
    const LISTING_RENT = 'rent';

    const STATUS_AVAILABLE = 'available';
    const STATUS_SOLD      = 'sold';
    const STATUS_RENTED    = 'rented';

    protected $fillable = [
        'property_type_id',
        'listing_type',
        'title',
        'description',
        'attributes',
        'price',
        'country_region_id',
        'root_region_id',
        'region_id',
        'address',
        'is_published',
        'published_at',
        'status',
        'available_at',
        'created_by',
        'deleted_by',
    ];

    protected $casts = [
        'attributes'   => 'array',
        'price'        => 'decimal:2',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'available_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Media Collections
    // -------------------------------------------------------------------------

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main_image')
            ->singleFile();

        $this->addMediaCollection('images');
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
    }

    /**
     * The most granular region this property belongs to (e.g. city/district).
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * Country-level region (top of the hierarchy).
     */
    public function countryRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'country_region_id');
    }

    /**
     * Root / state-level region (one level below country).
     */
    public function rootRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'root_region_id');
    }

    /**
     * User who created the listing.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who soft-deleted the listing.
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // -------------------------------------------------------------------------
    // Scout / Meilisearch
    // -------------------------------------------------------------------------

    /**
     * The Meilisearch index name for this model.
     */
    public function searchableAs(): string
    {
        return 'properties';
    }

    /**
     * Eager-load relationships when mass-importing via scout:import.
     */
    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['propertyType', 'region', 'rootRegion', 'countryRegion']);
    }

    /**
     * Build the searchable / filterable document sent to Meilisearch.
     *
     * Dynamic attributes are spread as flat top-level keys (attr_* prefix) rather
     * than nested under an "attributes" object. Meilisearch builds its inverted
     * index on scalar leaf values; nested objects require additional traversal on
     * every filter evaluation, so flat fields are significantly faster under heavy
     * attribute filtering (e.g. attr_surface > 100 AND attr_nombre_de_pieces = 3).
     */
    public function toSearchableArray(): array
    {
        $doc = [
            'id'                => $this->id,
            'title'             => $this->title,
            'description'       => $this->description,
            'listing_type'      => $this->listing_type,
            'status'            => $this->status,
            'price'             => (float) $this->price,
            'address'           => $this->address,
            'is_published'      => $this->is_published,

            // Relational fields
            'property_type_id'  => $this->property_type_id,
            'property_type'     => $this->propertyType?->title,
            'region_id'         => $this->region_id,
            'region'            => $this->region?->name,
            'root_region_id'    => $this->root_region_id,
            'root_region'       => $this->rootRegion?->name,
            'country_region_id' => $this->country_region_id,
            'country_region'    => $this->countryRegion?->name,

            // Timestamps as Unix integers for sorting
            'published_at'      => $this->published_at?->timestamp,
            'available_at'      => $this->available_at?->timestamp,
            'created_at'        => $this->created_at?->timestamp,
        ];

        // Spread each dynamic attribute as a flat top-level field.
        // Keys are ASCII-normalized and prefixed with "attr_" to:
        //   1. Avoid collisions with the fixed fields above
        //   2. Produce clean, predictable Meilisearch filter expressions
        //   3. Allow Meilisearch to build a direct inverted index per attribute
        foreach ($this->getAttribute('attributes') ?? [] as $key => $value) {
            $doc[PropertyAttributeHelper::normalizeAttributeKey($key)] = $value;
        }

        return $doc;
    }
}
