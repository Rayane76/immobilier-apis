<?php

namespace App\Support;

use App\Models\Property;

class PropertyTitleGenerator
{
    /**
     * Generate a human-readable title for the given property.
     *
     * Format: [Type] [attribute value + label] à [Wilaya - Commune]
     *
     * Requires the following relations to be loaded (or loadable):
     *   propertyType.propertyTypeTitleAttribute.attribute, rootRegion, region
     */
    public static function generate(Property $property): string
    {
        $property->loadMissing([
            'propertyType.propertyTypeTitleAttribute.attribute',
            'rootRegion',
            'region',
        ]);

        $propertyType = $property->propertyType;
        $titleParts   = [];

        // 1. Property type label
        $titleParts[] = ucfirst($propertyType->title);

        // 2. Attribute value + label if one is configured for title generation
        $titleAttributePivot = $propertyType->propertyTypeTitleAttribute->first();
        if ($titleAttributePivot && $titleAttributePivot->attribute) {
            $attribute    = $titleAttributePivot->attribute;
            $attributeKey = $attribute->title;
            $attrs        = $property->getAttribute('attributes') ?? [];

            if (isset($attrs[$attributeKey])) {
                $titleParts[] = trim($attrs[$attributeKey] . ' ' . $attribute->property_title_label);
            }
        }

        // 3. Location: à Wilaya - Commune
        $location = 'à ';
        if ($property->rootRegion) {
            $location .= $property->rootRegion->name;
        }
        if ($property->region) {
            $location .= ($property->rootRegion ? ' - ' : '') . $property->region->name;
        }

        if (trim($location) !== 'à') {
            $titleParts[] = $location;
        }

        return implode(' ', $titleParts);
    }
}
