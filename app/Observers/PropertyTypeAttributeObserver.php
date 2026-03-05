<?php

namespace App\Observers;

use App\Models\PropertyTypeAttribute;

class PropertyTypeAttributeObserver
{
    /**
     * CREATING:
     *   All ordering is scoped to the same property_type_id.
     *   - No order provided → append at end of this type's list.
     *   - Explicit order    → shift records at that position and above up.
     *
     * Note: fires only for direct Eloquent model operations (::create, ->save).
     * BelongsToMany::attach() / sync() / detach() bypass model events entirely —
     * pass the `order` value explicitly in the pivot data in those cases.
     */
    public function creating(PropertyTypeAttribute $pta): void
    {
        $scope = PropertyTypeAttribute::where('property_type_id', $pta->property_type_id);

        if (is_null($pta->order)) {
            $pta->order = ($scope->max('order') ?? 0) + 1;
        } else {
            (clone $scope)
                ->where('order', '>=', $pta->order)
                ->increment('order');
        }
    }

    /**
     * UPDATING:
     *   Only reorder when `order` actually changed.
     *   Moving down (e.g. 2 → 4): shift records [3..4] down by 1.
     *   Moving up   (e.g. 4 → 2): shift records [2..3] up   by 1.
     *   Scoped to the same property_type_id. Excludes self from shift.
     */
    public function updating(PropertyTypeAttribute $pta): void
    {
        if (! $pta->isDirty('order')) {
            return;
        }

        $old   = $pta->getOriginal('order');
        $new   = $pta->order;
        $scope = PropertyTypeAttribute::where('property_type_id', $pta->property_type_id)
            ->where('id', '!=', $pta->id);

        if ($new > $old) {
            (clone $scope)
                ->whereBetween('order', [$old + 1, $new])
                ->decrement('order');
        } elseif ($new < $old) {
            (clone $scope)
                ->whereBetween('order', [$new, $old - 1])
                ->increment('order');
        }
    }

    /**
     * DELETING:
     *   Close the gap within the same property_type_id.
     *   No SoftDeletes on this table — every delete is permanent.
     */
    public function deleting(PropertyTypeAttribute $pta): void
    {
        PropertyTypeAttribute::where('property_type_id', $pta->property_type_id)
            ->where('order', '>', $pta->order)
            ->decrement('order');
    }
}
