<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BuyerRequirement;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuyerRequirementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BuyerRequirement::with('user:id,name,email')
            ->where('status', 'open');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('city')) {
            $query->where('city', $request->input('city'));
        }

        if ($request->filled('max_budget')) {
            $query->where('budget_max', '<=', (float) $request->input('max_budget'));
        }

        $requirements = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $requirements,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:' . implode(',', [
                Listing::TYPE_VEHICLE,
                Listing::TYPE_REAL_ESTATE,
                Listing::TYPE_APARTMENT,
                Listing::TYPE_PRODUCT,
            ]),
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'city' => 'required|string|max:100',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'specifications' => 'nullable|array',
        ]);

        $requirement = BuyerRequirement::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'city' => $validated['city'],
            'budget_min' => $validated['budget_min'] ?? null,
            'budget_max' => $validated['budget_max'] ?? null,
            'specifications' => $validated['specifications'] ?? [],
            'status' => 'open',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Buyer requirement posted successfully. Matching sellers will be able to view and contact you.',
            'data' => $requirement,
        ], 201);
    }

    public function myRequirements(Request $request): JsonResponse
    {
        $requirements = BuyerRequirement::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $requirements,
        ]);
    }

    public function updateStatus(Request $request, BuyerRequirement $requirement): JsonResponse
    {
        if ($requirement->user_id !== $request->user()->id && !$request->user()->isSuperAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:open,fulfilled,closed',
        ]);

        $requirement->update(['status' => $validated['status']]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status updated.',
            'data' => $requirement,
        ]);
    }
}
