<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use SoftDeletes;

    // -------------------------------------------------------------------------
    // Type enum values (mirrors the DB enum)
    // -------------------------------------------------------------------------
    const TYPE_STRING  = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_BOOLEAN = 'boolean';

    protected $fillable = [
        'title',
        'description',
        'type',
        'property_title_label',
        'options',
        'min_value',
        'max_value',
        'is_filterable',
        'created_by',
    ];

    protected $casts = [
        'options'       => 'array',
        'min_value'     => 'decimal:8',
        'max_value'     => 'decimal:8',
        'is_filterable' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The property types that use this attribute (via pivot).
     */
    public function propertyTypes(): BelongsToMany
    {
        return $this->belongsToMany(PropertyType::class, 'property_type_attributes')
            ->using(PropertyTypeAttribute::class)
            ->withPivot(['is_required', 'order', 'created_by'])
            ->withTimestamps();
    }

    /**
     * Explicit pivot records for this attribute.
     */
    public function propertyTypeAttributes(): HasMany
    {
        return $this->hasMany(PropertyTypeAttribute::class);
    }

    /**
     * The user who created this attribute.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
