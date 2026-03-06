<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Cache all regions in memory to avoid queries inside the loop
        $allRegions = Region::all()->keyBy('id');
        $leafRegions = $allRegions->whereIn('type', ['commune', 'city'])->values();

        $propertyTypes = PropertyType::with('propertyTypeAttributes.attribute')->get();
        // Use only users with 'agent' or 'Super-Admin' roles
        $users = User::role(['agent', 'Super-Admin'])->pluck('id');

        if ($users->isEmpty()) {
            $users = User::pluck('id');
        }

        if ($users->isEmpty()) {
            $users = collect([User::factory()->create()->id]);
        }

        // Map hierarchy once for all leaf regions
        $regionHierarchyMap = [];
        foreach ($leafRegions as $region) {
            $rootRegionId = null;
            $countryRegionId = null;
            $current = $region;

            while ($current && $current->parent_id) {
                $parent = $allRegions->get($current->parent_id);
                if (!$parent) break;
                if ($parent->type === 'country') {
                    $countryRegionId = $parent->id;
                    break;
                }
                if ($parent->depth === 1) {
                    $rootRegionId = $parent->id;
                }
                $current = $parent;
            }
            $regionHierarchyMap[$region->id] = [
                'root_id' => $rootRegionId,
                'country_id' => $countryRegionId,
                'root_name' => $rootRegionId ? $allRegions->get($rootRegionId)->name : null
            ];
        }

        $totalRecords = 50000;
        $batchSize = 2000;

        $this->command->getOutput()->progressStart($totalRecords);

        for ($i = 0; $i < $totalRecords / $batchSize; $i++) {
            $batch = [];

            for ($j = 0; $j < $batchSize; $j++) {
                $type = $propertyTypes->random();
                $region = $leafRegions->random();
                $hierarchy = $regionHierarchyMap[$region->id];

                // --- Generate Attributes ---
                $attributesJson = [];
                foreach ($type->propertyTypeAttributes as $pta) {
                    $attr = $pta->attribute;
                    $value = match ($attr->type) {
                        'integer' => $faker->numberBetween($attr->min_value ?? 1, ($attr->min_value ?? 1) + 10),
                        'decimal' => $faker->randomFloat(2, $attr->min_value ?? 10, ($attr->min_value ?? 10) + 500),
                        'boolean' => $faker->boolean(),
                        'string'  => !empty($attr->options) ? $faker->randomElement($attr->options) : $faker->word(),
                        default   => null,
                    };
                    $attributesJson[$attr->title] = $value;
                }

                $listingType = $faker->randomElement([Property::LISTING_SALE, Property::LISTING_RENT]);
                $status = $faker->randomElement([Property::STATUS_AVAILABLE, Property::STATUS_SOLD, Property::STATUS_RENTED]);
                $isPublished = $faker->boolean(80);

                // --- Generate Title (Logic optimized) ---
                $titleParts = [$type->title];
                $titleAttributePivot = $type->propertyTypeAttributes->where('is_used_for_title', true)->first();
                if ($titleAttributePivot && isset($attributesJson[$titleAttributePivot->attribute->title])) {
                    $titleParts[] = trim($attributesJson[$titleAttributePivot->attribute->title] . ' ' . $titleAttributePivot->attribute->property_title_label);
                }

                $location = 'à ';
                if ($hierarchy['root_id']) {
                    $location .= $hierarchy['root_name'];
                }
                if ($region) {
                    $location .= ($hierarchy['root_id'] ? ' - ' : '') . $region->name;
                }
                if (trim($location) !== 'à') {
                    $titleParts[] = $location;
                }

                $batch[] = [
                    'property_type_id' => $type->id,
                    'listing_type' => $listingType,
                    'title' => implode(' ', $titleParts),
                    'description' => $faker->text(300), // Faster than paragraphs()
                    'attributes' => json_encode($attributesJson),
                    'price' => ($listingType === Property::LISTING_SALE) ? $faker->randomFloat(2, 50000, 1000000) : $faker->randomFloat(2, 500, 5000),
                    'country_region_id' => $hierarchy['country_id'],
                    'root_region_id' => $hierarchy['root_id'],
                    'region_id' => $region->id,
                    'address' => $faker->address,
                    'is_published' => $isPublished,
                    'published_at' => $isPublished ? $faker->dateTimeBetween('-1 year', 'now') : null,
                    'status' => $status,
                    'available_at' => $status === Property::STATUS_AVAILABLE ? now() : $faker->dateTimeBetween('now', '+6 months'),
                    'created_by' => $users->random(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('properties')->insert($batch);
            $this->command->getOutput()->progressAdvance($batchSize);
        }

        $this->command->getOutput()->progressFinish();

        // ─────────────────────────────────────────────────────────────────────
        // Phase 2: attach seed images to a representative sample.
        // Images are read from database/seeders/images/ and attached with
        // preservingOriginal() so the same source files can be reused across
        // multiple properties without being moved/deleted by MediaLibrary.
        // ─────────────────────────────────────────────────────────────────────
        $seedImageDir = database_path('seeders/images');
        $seedImages   = glob($seedImageDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);

        if (empty($seedImages)) {
            $this->command->getOutput()->writeln('');
            $this->command->getOutput()->writeln('<comment>[PropertySeeder] No seed images found in database/seeders/images/ — skipping media attachment.</comment>');
            return;
        }

        $sampleSize = 500;
        $sampleIds  = Property::inRandomOrder()->limit($sampleSize)->pluck('id');

        $this->command->getOutput()->writeln('');
        $this->command->getOutput()->writeln("[PropertySeeder] Attaching seed images to {$sampleIds->count()} sample properties...");
        $this->command->getOutput()->progressStart($sampleIds->count());

        foreach ($sampleIds as $id) {
            $property = Property::find($id);

            // Pick a random seed image for the cover
            $mainSrc = $seedImages[array_rand($seedImages)];
            $property->addMedia($mainSrc)
                ->preservingOriginal()
                ->usingFileName('main_' . $id . '.' . pathinfo($mainSrc, PATHINFO_EXTENSION))
                ->toMediaCollection('main_image');

            // 1–4 random gallery images
            $galleryCount = rand(1, 4);
            for ($k = 0; $k < $galleryCount; $k++) {
                $gallerySrc = $seedImages[array_rand($seedImages)];
                $property->addMedia($gallerySrc)
                    ->preservingOriginal()
                    ->usingFileName('gallery_' . $id . '_' . $k . '.' . pathinfo($gallerySrc, PATHINFO_EXTENSION))
                    ->toMediaCollection('images');
            }

            $this->command->getOutput()->progressAdvance();
        }

        $this->command->getOutput()->progressFinish();
    }
}
