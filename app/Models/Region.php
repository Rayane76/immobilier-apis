<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Region extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'type',
        'depth',
        'code',
        'created_by',
    ];

    protected $casts = [
        'depth' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * Direct parent region (e.g. city → state, state → country).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'parent_id');
    }

    /**
     * Direct children of this region.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Region::class, 'parent_id');
    }

    /**
     * Get all descendant IDs at any depth using a single recursive CTE.
     * Includes the current region's own ID.
     * e.g. Property::whereIn('region_id', $region->allDescendantIds())->get()
     *
     * @return int[]
     */
    public function allDescendantIds(): array
    {
        $result = DB::select("
            WITH RECURSIVE descendants AS (
                SELECT id FROM regions WHERE id = :id
                UNION ALL
                SELECT r.id FROM regions r
                INNER JOIN descendants d ON r.parent_id = d.id
            )
            SELECT id FROM descendants
        ", ['id' => $this->id]);

        return array_column($result, 'id');
    }

    /**
     * Properties whose immediate region is this one.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'region_id');
    }

    /**
     * Properties that reference this region as their country-level root.
     */
    public function countryProperties(): HasMany
    {
        return $this->hasMany(Property::class, 'country_region_id');
    }

    /**
     * Properties that reference this region as their root (top-level) region.
     */
    public function rootProperties(): HasMany
    {
        return $this->hasMany(Property::class, 'root_region_id');
    }

    /**
     * The user who created this region.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeAtDepth($query, int $depth)
    {
        return $query->where('depth', $depth);
    }
}
