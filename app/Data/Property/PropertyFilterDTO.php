<?php

namespace App\Data\Property;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Data;

class PropertyFilterDTO extends Data
{
    public function __construct(
        /** Full-text search query sent to Meilisearch */
        #[Sometimes, Nullable]
        public readonly ?string $q = null,

        #[Sometimes, Nullable, In('sale', 'rent')]
        public readonly ?string $listing_type = null,

        #[Sometimes, Nullable, In('available', 'sold', 'rented')]
        public readonly ?string $status = null,

        #[Sometimes, Nullable, Min(0)]
        public readonly ?float $price_min = null,

        #[Sometimes, Nullable, Min(0)]
        public readonly ?float $price_max = null,

        #[Sometimes, Nullable]
        public readonly ?int $property_type_id = null,

        #[Sometimes, Nullable]
        public readonly ?int $region_id = null,

        #[Sometimes, Nullable]
        public readonly ?int $country_region_id = null,

        #[Sometimes, Nullable]
        public readonly ?int $root_region_id = null,

        /** Return only published listings (default true for public API) */
        #[Sometimes, Nullable]
        public readonly ?bool $is_published = true,

        /**
         * When true, return only soft-deleted properties.
         * Requires ViewAnyDeleted:Property — enforced in PropertyController.
         */
        #[Sometimes, Nullable]
        public readonly ?bool $trashed = null,

        #[Sometimes, Min(1)]
        public readonly int $per_page = 15,
    ) {}
}
