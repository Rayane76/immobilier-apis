<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'order',
        'created_by',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * Attributes linked to this property type (via pivot).
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'property_type_attributes')
            ->using(PropertyTypeAttribute::class)
            ->withPivot(['is_required', 'order', 'created_by'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    /**
     * Required attributes only.
     */
    public function requiredAttributes(): BelongsToMany
    {
        return $this->attributes()->wherePivot('is_required', true);
    }

    /**
     * Explicit pivot records for this property type.
     */
    public function propertyTypeAttributes(): HasMany
    {
        return $this->hasMany(PropertyTypeAttribute::class);
    }

    /**
     * Properties that belong to this type.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /**
     * Get the attribute used for generating property titles for this type.
     */
    public function titleAttribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'id', 'id')
            ->whereIn('id', function ($query) {
                $query->select('attribute_id')
                    ->from('property_type_attributes')
                    ->whereColumn('property_type_id', 'property_types.id')
                    ->where('is_used_for_title', true);
            });
    }

    /**
     * Get the pivot record for the title attribute.
     */
    public function propertyTypeTitleAttribute(): HasMany
    {
        return $this->hasMany(PropertyTypeAttribute::class)
            ->where('is_used_for_title', true);
    }

    /**
     * The user who created this property type.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
