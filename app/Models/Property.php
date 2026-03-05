<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Property extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyFactory> */
    use HasFactory, SoftDeletes;

    // -------------------------------------------------------------------------
    // Enum constants (mirror the DB enums for type-safe usage)
    // -------------------------------------------------------------------------
    const LISTING_SALE = 'sale';
    const LISTING_RENT = 'rent';

    const UNIT_M2   = 'm2';
    const UNIT_FT2  = 'ft2';
    const UNIT_ARE  = 'are';
    const UNIT_HA   = 'ha';
    const UNIT_ACRE = 'acre';
    const UNIT_KM2  = 'km2';

    const STATUS_AVAILABLE = 'available';
    const STATUS_SOLD      = 'sold';
    const STATUS_RENTED    = 'rented';

    protected $fillable = [
        'property_type_id',
        'listing_type',
        'title',
        'surface',
        'surface_unit',
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
        'surface'      => 'decimal:2',
        'price'        => 'decimal:2',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'available_at' => 'datetime',
    ];

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
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForSale($query)
    {
        return $query->where('listing_type', self::LISTING_SALE);
    }

    public function scopeForRent($query)
    {
        return $query->where('listing_type', self::LISTING_RENT);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeOfType($query, int $propertyTypeId)
    {
        return $query->where('property_type_id', $propertyTypeId);
    }

    public function scopeInRegion($query, int $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    /**
     * Filter by a JSONB attribute value using Postgres containment (@>).
     * e.g. Property::withAttribute('rooms', 4)->get()
     */
    public function scopeWithAttribute($query, string $key, mixed $value)
    {
        return $query->whereRaw('attributes @> ?', [json_encode([$key => $value])]);
    }
}
