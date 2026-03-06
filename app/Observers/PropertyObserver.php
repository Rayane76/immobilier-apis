<?php

namespace App\Observers;

use App\Models\Property;
use App\Support\PropertyTitleGenerator;

class PropertyObserver
{
    /**
     * Handle the Property "creating" event.
     */
    public function creating(Property $property): void
    {
        $property->title = PropertyTitleGenerator::generate($property);
    }

    /**
     * Handle the Property "updating" event.
     */
    public function updating(Property $property): void
    {
        // Regenerate title if any of the dependencies change
        if ($property->isDirty(['property_type_id', 'attributes', 'root_region_id', 'region_id'])) {
            $property->title = PropertyTitleGenerator::generate($property);
        }

        if ($property->isDirty('is_published') && $property->is_published && !$property->published_at) {
            $property->published_at = now();
        }
    }
}
