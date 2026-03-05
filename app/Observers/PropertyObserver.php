<?php

namespace App\Observers;

use App\Models\Property;

class PropertyObserver
{
    /**
     * Handle the Property "creating" event.
     */
    public function creating(Property $property): void
    {
        // Load related models if not already eager loaded to generate title
        $property->loadMissing([
            'propertyType.propertyTypeTitleAttribute.attribute',
            'rootRegion',
            'region'
        ]);

        $property->title = $property->generateTitle();
    }

    /**
     * Handle the Property "updating" event.
     */
    public function updating(Property $property): void
    {
        // Regenerate title if any of the dependencies change
        if ($property->isDirty(['property_type_id', 'attributes', 'root_region_id', 'region_id'])) {
            $property->loadMissing([
                'propertyType.propertyTypeTitleAttribute.attribute',
                'rootRegion',
                'region'
            ]);
            $property->title = $property->generateTitle();
        }

        if ($property->isDirty('is_published') && $property->is_published && !$property->published_at) {
            $property->published_at = now();
        }
    }
}
