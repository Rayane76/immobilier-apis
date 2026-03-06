<?php

namespace App\Console\Commands;

use App\Models\Attribute;
use App\Support\PropertyAttributeHelper;
use Illuminate\Console\Command;
use Meilisearch\Client as MeilisearchClient;

class SyncPropertyMeilisearchFilters extends Command
{
    protected $signature   = 'scout:sync-property-filters';
    protected $description = 'Push the full filterableAttributes list (including dynamic attr_* keys) to the Meilisearch properties index.';

    /**
     * Static filterable fields that are always present on every property document.
     * Dynamic attr_* keys from the Attribute table are appended at runtime.
     */
    private const STATIC_FILTERABLE = [
        'listing_type',
        'status',
        'is_published',
        'price',
        'property_type_id',
        'property_type',
        'region_id',
        'root_region_id',
        'country_region_id',
    ];

    public function handle(): int
    {
        // Instantiate directly from config instead of relying on IoC injection.
        // Scout registers MeilisearchClient internally inside its engine factory and
        // does NOT guarantee a direct container binding — injecting it via handle()
        // would throw "Target class is not instantiable" in some Scout versions.
        $meilisearch = new MeilisearchClient(
            config('scout.meilisearch.host'),
            config('scout.meilisearch.key'),
        );

        // Build dynamic attr_* keys from every attribute currently in the DB
        $dynamicKeys = Attribute::query()
            ->select('title')
            ->pluck('title')
            ->map(fn(string $title) => PropertyAttributeHelper::normalizeAttributeKey($title))
            ->unique()
            ->values()
            ->all();

        $filterableAttributes = array_merge(self::STATIC_FILTERABLE, $dynamicKeys);

        try {
            $meilisearch
                ->index('properties')
                ->updateFilterableAttributes($filterableAttributes);
        } catch (\Throwable $e) {
            $this->error('[scout:sync-property-filters] Failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info(sprintf(
            '[scout:sync-property-filters] Synced %d filterable keys (%d static + %d dynamic attr_*) to Meilisearch.',
            count($filterableAttributes),
            count(self::STATIC_FILTERABLE),
            count($dynamicKeys),
        ));

        return self::SUCCESS;
    }
}
