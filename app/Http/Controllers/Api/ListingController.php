<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListingRequest;
use App\Http\Requests\UpdateListingRequest;
use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListingController extends Controller
{
    public function myListings(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Listing::with(['category', 'primaryImage', 'images'])
            ->where('user_id', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $listings = $query->latest()->paginate($request->input('per_page', 15));

        $counts = [
            'total' => Listing::where('user_id', $user->id)->count(),
            'published' => Listing::where('user_id', $user->id)->where('status', Listing::STATUS_PUBLISHED)->count(),
            'pending' => Listing::where('user_id', $user->id)->where('status', Listing::STATUS_PENDING)->count(),
            'draft' => Listing::where('user_id', $user->id)->where('status', Listing::STATUS_DRAFT)->count(),
            'rejected' => Listing::where('user_id', $user->id)->where('status', Listing::STATUS_REJECTED)->count(),
            'sold_rented' => Listing::where('user_id', $user->id)->whereIn('status', [Listing::STATUS_SOLD, Listing::STATUS_RENTED])->count(),
        ];

        $limit = $user->getListingLimit();
        $used = $user->getActiveListingCount();

        return response()->json([
            'status' => 'success',
            'data' => $listings,
            'counts' => $counts,
            'quota' => [
                'limit' => $limit,
                'used' => $used,
                'remaining' => max(0, $limit - $used),
                'can_create' => $user->canCreateListing(),
                'plan_name' => $user->activeSubscription?->plan?->name ?? 'Basic',
            ],
        ]);
    }

    public function store(StoreListingRequest $request): JsonResponse
    {
        $user = $request->user();

        // Extra guard: Check quota
        if (!$user->canCreateListing()) {
            return response()->json([
                'status' => 'error',
                'message' => "Listing limit reached ({$user->getListingLimit()}/{$user->getListingLimit()}). Please upgrade your subscription plan to add more listings.",
                'upgrade_required' => true,
            ], 422);
        }

        $validated = $request->validated();

        $listing = DB::transaction(function () use ($user, $validated, $request) {
            $status = $validated['status'] ?? Listing::STATUS_PENDING;

            $listing = Listing::create([
                'user_id' => $user->id,
                'category_id' => $validated['category_id'],
                'type' => $validated['type'],
                'title' => $validated['title'],
                'slug' => Str::slug($validated['title']) . '-' . Str::random(6),
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'currency' => $validated['currency'] ?? 'ETB',
                'city' => $validated['city'],
                'address' => $validated['address'] ?? null,
                'status' => $status,
                'year' => $validated['year'] ?? null,
                'bedrooms' => $validated['bedrooms'] ?? null,
                'listing_attributes' => $validated['listing_attributes'] ?? [],
            ]);

            // Handle uploaded images
            $isPrimary = true;
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('listings', 'public');
                    ListingImage::create([
                        'listing_id' => $listing->id,
                        'image_path' => $path,
                        'is_primary' => $isPrimary,
                    ]);
                    $isPrimary = false;
                }
            }

            // Handle direct image URLs (useful for demos or mock data)
            if (!empty($validated['image_urls'])) {
                foreach ($validated['image_urls'] as $url) {
                    if (!empty($url)) {
                        ListingImage::create([
                            'listing_id' => $listing->id,
                            'image_path' => $url,
                            'is_primary' => $isPrimary,
                        ]);
                        $isPrimary = false;
                    }
                }
            }

            return $listing;
        });

        $listing->load(['category', 'images', 'primaryImage']);

        return response()->json([
            'status' => 'success',
            'message' => $listing->status === Listing::STATUS_PENDING
                ? 'Listing submitted successfully and is pending admin approval.'
                : 'Listing saved as draft.',
            'data' => $listing,
        ], 201);
    }

    public function show(Listing $listing, Request $request): JsonResponse
    {
        $this->authorize('view', $listing);

        $listing->load(['category', 'images', 'primaryImage', 'user.profile']);

        return response()->json([
            'status' => 'success',
            'data' => $listing,
        ]);
    }

    public function update(UpdateListingRequest $request, Listing $listing): JsonResponse
    {
        $this->authorize('update', $listing);

        $validated = $request->validated();

        DB::transaction(function () use ($listing, $validated, $request) {
            $listing->update(array_filter([
                'category_id' => $validated['category_id'] ?? $listing->category_id,
                'title' => $validated['title'] ?? $listing->title,
                'description' => $validated['description'] ?? $listing->description,
                'price' => $validated['price'] ?? $listing->price,
                'city' => $validated['city'] ?? $listing->city,
                'address' => $validated['address'] ?? $listing->address,
                'status' => $validated['status'] ?? $listing->status,
                'year' => array_key_exists('year', $validated) ? $validated['year'] : $listing->year,
                'bedrooms' => array_key_exists('bedrooms', $validated) ? $validated['bedrooms'] : $listing->bedrooms,
                'listing_attributes' => $validated['listing_attributes'] ?? $listing->listing_attributes,
            ], fn($val) => $val !== null));

            // Remove selected images
            if (!empty($validated['remove_image_ids'])) {
                $imagesToRemove = ListingImage::where('listing_id', $listing->id)
                    ->whereIn('id', $validated['remove_image_ids'])
                    ->get();

                foreach ($imagesToRemove as $img) {
                    if (!str_starts_with($img->image_path, 'http')) {
                        Storage::disk('public')->delete($img->image_path);
                    }
                    $img->delete();
                }
            }

            // Upload new images
            if ($request->hasFile('images')) {
                $hasPrimary = $listing->images()->where('is_primary', true)->exists();
                foreach ($request->file('images') as $file) {
                    $path = $file->store('listings', 'public');
                    ListingImage::create([
                        'listing_id' => $listing->id,
                        'image_path' => $path,
                        'is_primary' => !$hasPrimary,
                    ]);
                    $hasPrimary = true;
                }
            }
        });

        $listing->load(['category', 'images', 'primaryImage']);

        return response()->json([
            'status' => 'success',
            'message' => 'Listing updated successfully.',
            'data' => $listing,
        ]);
    }

    public function destroy(Listing $listing): JsonResponse
    {
        $this->authorize('delete', $listing);

        $listing->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Listing deleted successfully.',
        ]);
    }

    public function changeStatus(Request $request, Listing $listing): JsonResponse
    {
        $this->authorize('update', $listing);

        $validated = $request->validate([
            'status' => 'required|string|in:draft,pending,sold,rented',
        ]);

        $listing->update(['status' => $validated['status']]);

        return response()->json([
            'status' => 'success',
            'message' => "Listing marked as {$validated['status']}.",
            'data' => $listing,
        ]);
    }
}

