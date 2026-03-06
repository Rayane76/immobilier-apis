<?php

namespace App\Data\Property;

use App\Rules\ValidPropertyAttributes;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class UpdatePropertyDTO extends Data
{
    public function __construct(
        #[Sometimes, Exists('property_types', 'id')]
        public readonly int|Optional $property_type_id,

        #[Sometimes, In('sale', 'rent')]
        public readonly string|Optional $listing_type,

        #[Sometimes, Nullable, Max(500)]
        public readonly string|null|Optional $title,

        #[Sometimes, Nullable, Max(5000)]
        public readonly string|null|Optional $description,

        #[Sometimes, Nullable]
        public readonly array|null|Optional $attributes,

        #[Sometimes, Nullable, Min(0)]
        public readonly float|null|Optional $price,

        #[Sometimes, Exists('regions', 'id')]
        public readonly int|Optional $country_region_id,

        #[Sometimes, Exists('regions', 'id')]
        public readonly int|Optional $root_region_id,

        #[Sometimes, Exists('regions', 'id')]
        public readonly int|Optional $region_id,

        #[Sometimes, Nullable, Max(1000)]
        public readonly string|null|Optional $address,

        #[Sometimes, Nullable]
        public readonly bool|null|Optional $is_published,

        #[Sometimes, Nullable]
        public readonly string|null|Optional $published_at,

        #[Sometimes, Nullable, In('available', 'sold', 'rented')]
        public readonly string|null|Optional $status,

        #[Sometimes, Nullable]
        public readonly string|null|Optional $available_at,

        /** Replace the cover image */
        public readonly ?UploadedFile $main_image = null,
    ) {}

    /**
     * Additional validation rules that cannot be expressed as constructor-level attributes.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $propertyTypeId = (int) request()->input('property_type_id') ?: null;

        if ($propertyTypeId === null && $routeId = request()->route('id')) {
            $propertyTypeId = \App\Models\Property::withTrashed()
                ->where('id', $routeId)
                ->value('property_type_id');
        }

        return [
            'attributes'      => ['nullable', 'array', new ValidPropertyAttributes($propertyTypeId)],
            'main_image'      => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'images'          => ['nullable', 'array', 'max:20'],
            'images.*'        => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_images'   => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
        ];
    }
}
