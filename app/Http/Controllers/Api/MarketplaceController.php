<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public const ETHIOPIAN_CITIES = [
        'Addis Ababa',
        'Hawassa',
        'Adama',
        'Bahir Dar',
        'Dire Dawa',
        'Gondar',
        'Mekelle',
        'Bishoftu',
        'Jimma',
        'Dessie',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = Listing::query()
            ->with(['user.profile', 'category', 'primaryImage', 'images'])
            ->published()
            ->filter($request->all())
            ->sort($request->input('sort'));

        $listings = $query->paginate($request->input('per_page', 12));

        $listings->getCollection()->transform(function ($listing) use ($request) {
            return $this->formatListing($listing, $request->user());
        });

        return response()->json([
            'status' => 'success',
            'data' => $listings,
        ]);
    }

    public function show(string $slug, Request $request): JsonResponse
    {
        $listing = Listing::with(['user.profile', 'category', 'images'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Check view authorization if not published
        if ($listing->status !== Listing::STATUS_PUBLISHED) {
            $user = $request->user();
            if (!$user || ($user->id !== $listing->user_id && !$user->isSuperAdmin())) {
                abort(404, 'Listing not found.');
            }
        }

        // Increment view count
        $listing->increment('views_count');

        return response()->json([
            'status' => 'success',
            'data' => $this->formatListing($listing, $request->user(), true),
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        $query = Category::where('is_active', true);

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $categories = $query->withCount(['listings' => function ($q) {
            $q->where('status', Listing::STATUS_PUBLISHED);
        }])->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    public function cities(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => self::ETHIOPIAN_CITIES,
        ]);
    }

    protected function formatListing(Listing $listing, $user = null, bool $detailed = false): array
    {
        $isFavorited = false;
        if ($user) {
            $isFavorited = $listing->favorites()->where('user_id', $user->id)->exists();
        }

        $data = [
            'id' => $listing->id,
            'user_id' => $listing->user_id,
            'category_id' => $listing->category_id,
            'category_name' => $listing->category?->name,
            'type' => $listing->type,
            'title' => $listing->title,
            'slug' => $listing->slug,
            'description' => $listing->description,
            'price' => (float) $listing->price,
            'currency' => $listing->currency ?: 'ETB',
            'city' => $listing->city,
            'address' => $listing->address,
            'status' => $listing->status,
            'rejection_reason' => $listing->rejection_reason,
            'year' => $listing->year,
            'bedrooms' => $listing->bedrooms,
            'listing_attributes' => $listing->listing_attributes ?: [],
            'views_count' => $listing->views_count,
            'featured' => $listing->featured,
            'primary_image' => $listing->getPrimaryImageUrl(),
            'images' => $listing->images->map(fn($img) => [
                'id' => $img->id,
                'url' => $img->url,
                'is_primary' => $img->is_primary,
            ]),
            'seller' => [
                'id' => $listing->user?->id,
                'name' => $listing->user?->name,
                'username' => $listing->user?->username,
                'avatar' => $listing->user?->getAvatarUrl(),
                'city' => $listing->user?->profile?->city ?? 'Addis Ababa',
                'is_verified' => (bool) $listing->user?->profile?->is_verified,
            ],
            'is_favorited' => $isFavorited,
            'created_at' => $listing->created_at?->toIso8601String(),
        ];

        return $data;
    }
}

