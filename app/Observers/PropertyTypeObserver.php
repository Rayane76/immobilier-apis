<?php

namespace App\Observers;

use App\Models\PropertyType;

class PropertyTypeObserver
{
    /**
     * CREATING:
     *   - No order provided  → append at the end (max + 1).
     *   - Explicit order provided → shift every record at that position
     *     and above up by 1 to make room.
     */
    public function creating(PropertyType $type): void
    {
        if (is_null($type->order)) {
            $type->order = (PropertyType::max('order') ?? 0) + 1;
        } else {
            PropertyType::where('order', '>=', $type->order)
                ->increment('order');
        }
    }

    /**
     * UPDATING:
     *   Moving down (e.g. 2 → 5): shift records [3..5] down by 1.
     *   Moving up   (e.g. 5 → 2): shift records [2..4] up   by 1.
     *   Exclude the current record from the shift to avoid self-collision.
     */
    public function updating(PropertyType $type): void
    {
        if (! $type->isDirty('order')) {
            return;
        }

        $old = $type->getOriginal('order');
        $new = $type->order;

        if ($new > $old) {
            PropertyType::where('id', '!=', $type->id)
                ->whereBetween('order', [$old + 1, $new])
                ->decrement('order');
        } elseif ($new < $old) {
            PropertyType::where('id', '!=', $type->id)
                ->whereBetween('order', [$new, $old - 1])
                ->increment('order');
        }
    }

    /**
     * DELETING:
     *   Close the gap left by the removed record so the list stays contiguous.
     *   Skip if already soft-deleted (forceDelete) — gap was closed on the
     *   original soft-delete and must not be closed a second time.
     */
    public function deleting(PropertyType $type): void
    {
        if ($type->trashed()) {
            return;
        }

        PropertyType::where('order', '>', $type->order)
            ->decrement('order');
    }

    /**
     * RESTORING:
     *   Re-open a slot at the record's stored order position so it slots
     *   back into the same logical position it had before deletion.
     */
    public function restoring(PropertyType $type): void
    {
        PropertyType::where('order', '>=', $type->order)
            ->increment('order');
    }
}
