<?php

namespace App\Observers;

use App\Models\Region;
use Illuminate\Support\Facades\DB;

class RegionObserver
{
    /**
     * CREATING:
     *   Derive depth from parent — callers never need to set it manually.
     */
    public function creating(Region $region): void
    {
        $region->depth = $region->parent_id === null
            ? 0
            : (Region::find($region->parent_id)?->depth ?? -1) + 1;
    }

    /**
     * UPDATING:
     *   If parent_id changed, recompute depth for this node before the row
     *   is written. The cascade to descendants happens in `updated`.
     */
    public function updating(Region $region): void
    {
        if ($region->isDirty('parent_id')) {
            $region->depth = $region->parent_id === null
                ? 0
                : (Region::find($region->parent_id)?->depth ?? -1) + 1;
        }
    }

    /**
     * UPDATED:
     *   When depth changed (always a consequence of parent_id change),
     *   cascade to every descendant via a single recursive CTE UPDATE.
     *   Formula: descendant.depth = this.depth + its distance from this node.
     */
    public function updated(Region $region): void
    {
        if (! $region->wasChanged('depth')) {
            return;
        }

        DB::statement("
            WITH RECURSIVE descendants AS (
                SELECT id, 1 AS gap
                FROM   regions
                WHERE  parent_id = :root_id
                UNION ALL
                SELECT r.id, d.gap + 1
                FROM   regions r
                INNER JOIN descendants d ON r.parent_id = d.id
            )
            UPDATE regions
            SET    depth = :base + descendants.gap
            FROM   descendants
            WHERE  regions.id = descendants.id
        ", ['root_id' => $region->id, 'base' => $region->depth]);
    }

    /**
     * DELETING (soft-delete only):
     *   Cascade soft-delete to all currently active descendants in one
     *   recursive CTE UPDATE.
     *   Skip on forceDelete — the DB-level CASCADE constraint handles that.
     */
    public function deleting(Region $region): void
    {
        if ($region->isForceDeleting()) {
            return;
        }

        DB::statement("
            WITH RECURSIVE descendants AS (
                SELECT id FROM regions
                WHERE  parent_id = :id AND deleted_at IS NULL
                UNION ALL
                SELECT r.id FROM regions r
                INNER JOIN descendants d ON r.parent_id = d.id
                WHERE  r.deleted_at IS NULL
            )
            UPDATE regions
            SET    deleted_at = NOW()
            WHERE  id IN (SELECT id FROM descendants)
        ", ['id' => $region->id]);
    }

    /**
     * RESTORING:
     *   Restore only descendants whose deleted_at >= this region's deleted_at,
     *   meaning they were cascade-deleted when the parent was deleted — not
     *   independently deleted before the parent was removed.
     */
    public function restoring(Region $region): void
    {
        DB::statement("
            WITH RECURSIVE descendants AS (
                SELECT id FROM regions
                WHERE  parent_id = :id
                UNION ALL
                SELECT r.id FROM regions r
                INNER JOIN descendants d ON r.parent_id = d.id
            )
            UPDATE regions
            SET    deleted_at = NULL
            WHERE  id IN (SELECT id FROM descendants)
            AND    deleted_at >= :deleted_at
        ", ['id' => $region->id, 'deleted_at' => $region->deleted_at]);
    }
}
