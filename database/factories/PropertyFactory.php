<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Cache these for performance during large seeds if needed, 
        // but for now, we'll fetch them. In the Seeder, we'll optimize.
        $propertyType = PropertyType::with('propertyTypeAttributes.attribute')->inRandomOrder()->first();

        // Find a random granular region (commune or city)
        $region = Region::whereIn('type', ['commune', 'city'])->inRandomOrder()->first();

        // Resolve hierarchy
        $rootRegion = null;
        $countryRegion = null;

        if ($region) {
            $current = $region;
            while ($current->parent_id) {
                $parent = Region::find($current->parent_id);
                if (!$parent) break;
                if ($parent->type === 'country') {
                    $countryRegion = $parent;
                    break;
                }
                // Convention: depth 1 is usually root/state/wilaya/region
                if ($parent->depth === 1) {
                    $rootRegion = $parent;
                }
                $current = $parent;
            }
        }

        $attributes = [];
        foreach ($propertyType->propertyTypeAttributes as $pta) {
            $attr = $pta->attribute;
            $value = null;

            switch ($attr->type) {
                case 'integer':
                    $value = fake()->numberBetween($attr->min_value ?? 1, ($attr->min_value ?? 1) + 10);
                    break;
                case 'decimal':
                    $value = fake()->randomFloat(2, $attr->min_value ?? 10, ($attr->min_value ?? 10) + 500);
                    break;
                case 'boolean':
                    $value = fake()->boolean();
                    break;
                case 'string':
                    if (!empty($attr->options)) {
                        $value = fake()->randomElement($attr->options);
                    } else {
                        $value = fake()->word();
                    }
                    break;
            }

            $attributes[$attr->title] = $value;
        }

        $listingType = fake()->randomElement([Property::LISTING_SALE, Property::LISTING_RENT]);
        $status = fake()->randomElement([Property::STATUS_AVAILABLE, Property::STATUS_SOLD, Property::STATUS_RENTED]);
        $isPublished = fake()->boolean(80);

        return [
            'property_type_id' => $propertyType->id,
            'listing_type' => $listingType,
            'title' => 'Temporary Title', // Will be overwritten in afterCreating
            'description' => fake()->paragraphs(3, true),
            'attributes' => $attributes,
            'price' => $listingType === Property::LISTING_SALE
                ? fake()->randomFloat(2, 50000, 1000000)
                : fake()->randomFloat(2, 500, 5000),
            'country_region_id' => $countryRegion?->id,
            'root_region_id' => $rootRegion?->id,
            'region_id' => $region?->id,
            'address' => fake()->address(),
            'is_published' => $isPublished,
            'published_at' => $isPublished ? fake()->dateTimeBetween('-1 year', 'now') : null,
            'status' => $status,
            'available_at' => $status === Property::STATUS_AVAILABLE ? now() : fake()->dateTimeBetween('now', '+6 months'),
            'created_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
        ];
    }

    /**
     * Configure the factory.
     */
    public function configure()
    {
        return $this->afterMaking(function (Property $property) {
            // Ensure title is generated correctly
            $property->title = $property->generateTitle();
        });
    }
}
