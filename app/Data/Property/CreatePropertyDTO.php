<?php

namespace App\Data\Property;

use App\Rules\ValidPropertyAttributes;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class CreatePropertyDTO extends Data
{
    public function __construct(
        #[Required, Exists('property_types', 'id')]
        public readonly int $property_type_id,

        #[Required, In('sale', 'rent')]
        public readonly string $listing_type,

        /** Auto-generated if omitted */
        #[Nullable, Max(500)]
        public readonly ?string $title = null,

        #[Nullable, Max(5000)]
        public readonly ?string $description = null,

        /** JSON object of dynamic attribute key-value pairs */
        #[Nullable]
        public readonly ?array $attributes = null,

        #[Nullable, Min(0)]
        public readonly ?float $price = null,

        #[Required, Exists('regions', 'id')]
        public readonly int $country_region_id,

        #[Required, Exists('regions', 'id')]
        public readonly int $root_region_id,

        #[Required, Exists('regions', 'id')]
        public readonly int $region_id,

        #[Nullable, Max(1000)]
        public readonly ?string $address = null,

        #[Nullable]
        public readonly ?bool $is_published = false,

        #[Nullable]
        public readonly ?string $published_at = null,

        #[Nullable, In('available', 'sold', 'rented')]
        public readonly ?string $status = 'available',

        #[Nullable]
        public readonly ?string $available_at = null,

        /** Single cover image — required on creation */
        #[Required]
        public readonly UploadedFile $main_image,
    ) {}

    /**
     * Additional validation rules that cannot be expressed as constructor-level attributes.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $propertyTypeId = (int) request()->input('property_type_id') ?: null;

        return [
            'attributes'   => ['nullable', 'array', new ValidPropertyAttributes($propertyTypeId)],
            'main_image'   => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'images'       => ['nullable', 'array', 'max:20'],
            'images.*'     => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
