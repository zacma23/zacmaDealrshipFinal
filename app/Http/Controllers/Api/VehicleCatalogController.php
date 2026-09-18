<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VehicleCatalogController extends Controller
{
    // ── PUBLIC ENDPOINTS ──────────────────────────────────────────────────────

    /**
     * List all active vehicle brands (used by listing form dropdowns)
     */
    public function brands(): JsonResponse
    {
        $brands = VehicleBrand::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->select(['id', 'name', 'slug'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $brands,
        ]);
    }

    /**
     * List active models for a brand
     */
    public function models(VehicleBrand $brand): JsonResponse
    {
        $models = $brand->activeModels()->select(['id', 'name', 'slug', 'body_type'])->get();

        return response()->json([
            'status' => 'success',
            'data'   => $models,
        ]);
    }

    // ── ADMIN ENDPOINTS (Super Admin only — enforced in routes via middleware) ─

    /**
     * List all brands including inactive (admin view)
     */
    public function adminBrands(): JsonResponse
    {
        $brands = VehicleBrand::withCount('models')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $brands,
        ]);
    }

    /**
     * List all models for a brand including inactive
     */
    public function adminModels(VehicleBrand $brand): JsonResponse
    {
        $models = $brand->models()->get();

        return response()->json([
            'status' => 'success',
            'data'   => $models,
        ]);
    }

    /**
     * Create a new vehicle brand
     */
    public function storeBrand(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100|unique:vehicle_brands,name',
            'is_active'  => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $brand = VehicleBrand::create([
            'name'       => $validated['name'],
            'slug'       => Str::slug($validated['name']),
            'is_active'  => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Vehicle brand created.',
            'data'    => $brand,
        ], 201);
    }

    /**
     * Update a vehicle brand (name, active status)
     */
    public function updateBrand(Request $request, VehicleBrand $brand): JsonResponse
    {
        $validated = $request->validate([
            'name'       => ['sometimes', 'required', 'string', 'max:100', Rule::unique('vehicle_brands', 'name')->ignore($brand->id)],
            'is_active'  => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $brand->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Brand updated.',
            'data'    => $brand->fresh(),
        ]);
    }

    /**
     * Add a model to a brand
     */
    public function storeModel(Request $request, VehicleBrand $brand): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'body_type' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $slug = Str::slug($validated['name']);

        // Prevent duplicate slug within same brand
        if (VehicleModel::where('brand_id', $brand->id)->where('slug', $slug)->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => "Model '{$validated['name']}' already exists for {$brand->name}.",
            ], 422);
        }

        $model = VehicleModel::create([
            'brand_id'  => $brand->id,
            'name'      => $validated['name'],
            'slug'      => $slug,
            'body_type' => $validated['body_type'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Model added.',
            'data'    => $model,
        ], 201);
    }

    /**
     * Update a vehicle model
     */
    public function updateModel(Request $request, VehicleModel $model): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'sometimes|required|string|max:100',
            'body_type' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $model->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Model updated.',
            'data'    => $model->fresh(),
        ]);
    }
}
