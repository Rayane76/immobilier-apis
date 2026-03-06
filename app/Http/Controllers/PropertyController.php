<?php

namespace App\Http\Controllers;

use App\Data\Property\CreatePropertyDTO;
use App\Data\Property\PropertyFilterDTO;
use App\Data\Property\UpdatePropertyDTO;
use App\Models\Property;
use App\Services\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Properties
 *
 * Manage real estate listings. Browsing and searching are public; write operations require authentication.
 */
class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyService $propertyService,
    ) {}

    // -------------------------------------------------------------------------
    // Public / guest-accessible
    // -------------------------------------------------------------------------

    /**
     * GET /api/properties
     *
     * Paginated list — supports full-text search (q=) via Meilisearch and
     * attribute filtering via query-string parameters.
     * Pass ?trashed=true to browse soft-deleted listings (requires ViewAnyDeleted:Property).
     *
     * @unauthenticated
     */
    public function index(PropertyFilterDTO $filter, Request $request): JsonResponse
    {
        $this->authorize('viewAny', Property::class);

        if ($filter->trashed) {
            $this->authorize('viewAnyDeleted', Property::class);
        }

        if ($filter->is_published === false) {
            $this->authorize('viewAnyUnpublished', Property::class);
        }

        return response()->json($this->propertyService->paginate($filter, $request->user()));
    }

    /**
     * GET /api/properties/{id}
     *
     * Single property with main_image + full images gallery.
     * Soft-deleted properties are visible only to users with ViewDeleted:Property
     * (agents may only view their own deleted listings; Super-Admin sees all).
     * We always search including trashed so we can return 403 instead of 404
     * when the record exists but the caller lacks permission.
     *
     * @unauthenticated
     */
    public function show(int $id): JsonResponse
    {
        $property = $this->propertyService->findTrashedModelOrFail($id);

        if ($property->trashed()) {
            $this->authorize('viewDeleted', $property);
        } elseif (!$property->is_published) {
            $this->authorize('viewUnpublished', $property);
        } else {
            $this->authorize('view', $property);
        }

        return response()->json($this->propertyService->show($property));
    }

    // -------------------------------------------------------------------------
    // Authenticated write operations
    // -------------------------------------------------------------------------

    /**
     * POST /api/properties
     *
     * Create a new listing. Accepts multipart/form-data for file uploads.
     * Title is auto-generated when omitted.
     *
     * @header Content-Type multipart/form-data
     */
    public function store(CreatePropertyDTO $data, Request $request): JsonResponse
    {
        $this->authorize('create', Property::class);

        return response()->json(
            $this->propertyService->create($data, $request->user()->id, $request),
            201
        );
    }

    /**
     * POST /api/properties/{id}   (multipart-safe; clients may also use PATCH)
     *
     * Partial update — only supplied fields are changed.
     * Pass remove_images[] with media IDs to delete gallery images.
     *
     * @header Content-Type multipart/form-data
     */
    public function update(UpdatePropertyDTO $data, int $id, Request $request): JsonResponse
    {
        $property = $this->propertyService->findModelOrFail($id);

        $this->authorize('update', $property);

        return response()->json(
            $this->propertyService->update($property, $data, $request)
        );
    }

    /**
     * DELETE /api/properties/{id}
     *
     * Soft-delete. The actor's ID is stamped as deleted_by.
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $property = $this->propertyService->findModelOrFail($id);

        $this->authorize('delete', $property);

        $this->propertyService->delete($property, $request->user()->id);

        return response()->json(null, 204);
    }

    /**
     * DELETE /api/properties/{id}/force
     *
     * Permanently removes the record and all associated media.
     */
    public function forceDelete(int $id, Request $request): JsonResponse
    {
        // We need to find the model (possibly soft-deleted) before force-deleting.
        $property = $this->propertyService->findTrashedModelOrFail($id);

        $this->authorize('forceDelete', $property);

        $this->propertyService->forceDelete($property);

        return response()->json(null, 204);
    }

    /**
     * PATCH /api/properties/{id}/restore
     *
     * Restore a previously soft-deleted listing.
     */
    public function restore(int $id): JsonResponse
    {
        $property = $this->propertyService->findTrashedModelOrFail($id);

        $this->authorize('restore', $property);

        $this->propertyService->restore($property);

        return response()->json($this->propertyService->show($property));
    }
}
