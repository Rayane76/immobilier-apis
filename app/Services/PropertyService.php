<?php

namespace App\Services;

use App\Data\Property\CreatePropertyDTO;
use App\Data\Property\PropertyDTO;
use App\Data\Property\PropertyFilterDTO;
use App\Data\Property\UpdatePropertyDTO;
use App\Models\Property;
use App\Models\User;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PropertyService
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
    ) {}

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    /**
     * Paginated list with main_image_url attached to each item.
     * Uses Meilisearch when a search query is present, Eloquent for trashed.
     *
     * When browsing trashed properties, $user is used to determine ownership
     * scoping: agents (hasDirectPermission) are scoped to their own records;
     * Super-Admin (granted via Gate::before, not directly) sees everything.
     */
    public function paginate(PropertyFilterDTO $filter, ?User $user = null): LengthAwarePaginator
    {
        $ownedBy = null;

        if ($filter->trashed && $user !== null) {
            // hasDirectPermission = false means Super-Admin granted it via Gate::before
            $ownedBy = $user->hasDirectPermission('ViewAnyDeleted:Property')
                ? $user->id
                : null;
        }

        return $this->propertyRepository
            ->paginate($filter, $ownedBy)
            ->through(fn(Property $property) => PropertyDTO::fromModel($property, false));
    }

    /**
     * Fetch the raw Eloquent model — used by the controller for policy checks.
     */
    public function findModelOrFail(int $id): Property
    {
        $property = $this->propertyRepository->findById($id);

        abort_unless($property, 404, 'Property not found.');

        return $property;
    }

    /**
     * Find including soft-deleted rows (needed before restoring).
     */
    public function findTrashedModelOrFail(int $id): Property
    {
        $property = $this->propertyRepository->findByIdWithTrashed($id);

        abort_unless($property, 404, 'Property not found.');

        return $property;
    }

    /**
     * Single-property response with full media gallery.
     */
    public function show(Property $property): PropertyDTO
    {
        return PropertyDTO::fromModel($property, true);
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    public function create(CreatePropertyDTO $data, int $userId, Request $request): PropertyDTO
    {
        $property = $this->propertyRepository->create($data, $userId);

        // Auto-generate title if the caller did not supply one
        if (empty($data->title)) {
            $property->title = $property->generateTitle();
            $property->save();
        }

        $this->syncMedia($property, $data->main_image, $request->file('images'));

        return PropertyDTO::fromModel($property->load(['propertyType', 'region', 'rootRegion', 'countryRegion']), true);
    }

    public function update(Property $property, UpdatePropertyDTO $data, Request $request): PropertyDTO
    {
        $property = $this->propertyRepository->update($property, $data);

        $this->syncMedia($property, $data->main_image, $request->file('images'), $request->input('remove_images', []));

        // Reload relations in case property_type_id / region_id etc. changed
        $property->load(['propertyType', 'region', 'rootRegion', 'countryRegion']);

        return PropertyDTO::fromModel($property, true);
    }

    public function delete(Property $property, int $userId): void
    {
        $this->propertyRepository->delete($property, $userId);
    }

    public function forceDelete(Property $property): void
    {
        // Clear all media before permanently deleting
        $property->clearMediaCollection('main_image');
        $property->clearMediaCollection('images');

        $this->propertyRepository->forceDelete($property);
    }

    public function restore(Property $property): void
    {
        $this->propertyRepository->restore($property);
    }

    // -------------------------------------------------------------------------
    // Media helpers
    // -------------------------------------------------------------------------

    /**
     * Synchronise media collections after a create or update.
     *
     * Every received image is converted to WebP before being stored, so only
     * WebP files land in MinIO — no original JPG/PNG is ever uploaded.
     *
     * @param  \Illuminate\Http\UploadedFile|null    $mainImage
     * @param  \Illuminate\Http\UploadedFile[]|null  $newImages
     * @param  int[]                                 $removeImageIds  Media IDs to delete
     */
    private function syncMedia(
        Property $property,
        mixed $mainImage,
        mixed $newImages,
        array $removeImageIds = [],
    ): void {
        // Replace main cover image
        if ($mainImage !== null) {
            $property->clearMediaCollection('main_image');
            $property->addMedia($this->toWebp($mainImage))
                ->usingFileName('main_' . $property->id . '.webp')
                ->toMediaCollection('main_image');
        }

        // Remove individually selected gallery images
        if (!empty($removeImageIds)) {
            $property->media()
                ->whereIn('id', $removeImageIds)
                ->get()
                ->each(fn($m) => $m->delete());
        }

        // Append new gallery images
        if (!empty($newImages)) {
            foreach ($newImages as $index => $image) {
                $property->addMedia($this->toWebp($image))
                    ->usingFileName('gallery_' . $property->id . '_' . $index . '_' . time() . '.webp')
                    ->toMediaCollection('images');
            }
        }
    }

    /**
     * Convert any uploaded image (JPG, PNG, GIF, WebP …) to a WebP temp file.
     *
     * Uses PHP's GD extension via imagecreatefromstring() so it handles all
     * common formats without needing to know the source type upfront.
     * The returned path is a temp file; Spatie MediaLibrary moves it to the
     * configured disk (MinIO), so nothing persists in /tmp after the request.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string  Absolute path to the converted WebP temp file
     */
    private function toWebp(\Illuminate\Http\UploadedFile $file): string
    {
        $image = imagecreatefromstring(file_get_contents($file->getRealPath()));

        $path = tempnam(sys_get_temp_dir(), 'webp_') . '.webp';
        imagewebp($image, $path, 85);
        unset($image);

        return $path;
    }
}
