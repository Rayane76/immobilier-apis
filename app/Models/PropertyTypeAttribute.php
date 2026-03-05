<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PropertyTypeAttribute extends Pivot
{
    protected $table = 'property_type_attributes';

    /**
     * Pivot tables get no auto-incrementing PK by default; we explicitly have
     * an `id` column so re-enable it.
     */
    public $incrementing = true;

    protected $fillable = [
        'property_type_id',
        'attribute_id',
        'is_required',
        'is_used_for_title',
        'order',
        'created_by',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_used_for_title' => 'boolean',
        'order'       => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
