<?php

namespace App\Observers;

use App\Models\Attribute;
use Illuminate\Support\Facades\Artisan;

class AttributeObserver
{
    /**
     * Re-sync Meilisearch filterableAttributes whenever an attribute is added,
     * renamed, or deleted so the index always reflects the current attribute set.
     */
    public function created(Attribute $attribute): void
    {
        $this->sync();
    }

    public function updated(Attribute $attribute): void
    {
        // Only re-sync if the title changed (which changes the attr_* key)
        if ($attribute->wasChanged('title')) {
            $this->sync();
        }
    }

    public function deleted(Attribute $attribute): void
    {
        $this->sync();
    }

    private function sync(): void
    {
        // This is a single fast HTTP call to the Meilisearch API, so run it
        // synchronously — no need to queue it.
        Artisan::call('scout:sync-property-filters');
    }
}
