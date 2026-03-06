<?php

namespace App\Data\Property;

use App\Models\Property;
use Spatie\LaravelData\Data;

class PropertyDTO extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $listing_type,
        public readonly string $status,
        public readonly ?float $price,
        public readonly ?string $address,
        public readonly bool $is_published,

        public readonly int $property_type_id,
        public readonly ?string $property_type,

        public readonly int $region_id,
        public readonly ?string $region,

        public readonly int $root_region_id,
        public readonly ?string $root_region,

        public readonly int $country_region_id,
        public readonly ?string $country_region,

        public readonly ?array $attributes,

        public readonly ?string $main_image_url,

        /** @var string[]|null */
        public readonly ?array $images,

        public readonly ?string $published_at,
        public readonly ?string $available_at,
        public readonly ?string $created_at,
    ) {}

    /**
     * Build a PropertyDTO from an Eloquent model.
     *
     * @param  bool  $withImages  Set to true on single-resource responses to
     *                            include the full images gallery. For paginated
     *                            lists pass false to avoid loading all images.
     */
    public static function fromModel(Property $property, bool $withImages = false): self
    {
        return new self(
            id: $property->id,
            title: $property->title,
            description: $property->description,
            listing_type: $property->listing_type,
            status: $property->status,
            price: $property->price !== null ? (float) $property->price : null,
            address: $property->address,
            is_published: $property->is_published,

            property_type_id: $property->property_type_id,
            property_type: $property->propertyType?->title,

            region_id: $property->region_id,
            region: $property->region?->name,

            root_region_id: $property->root_region_id,
            root_region: $property->rootRegion?->name,

            country_region_id: $property->country_region_id,
            country_region: $property->countryRegion?->name,

            attributes: $property->attributes,

            main_image_url: ($url = $property->getFirstMediaUrl('main_image')) !== '' ? $url : null,

            images: $withImages
                ? $property->getMedia('images')->map(fn($m) => $m->getUrl())->toArray()
                : null,

            published_at: $property->published_at?->toISOString(),
            available_at: $property->available_at?->toISOString(),
            created_at: $property->created_at?->toISOString(),
        );
    }
}
