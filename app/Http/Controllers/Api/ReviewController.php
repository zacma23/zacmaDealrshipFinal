<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Listing $listing): JsonResponse
    {
        $reviews = Review::with('user:id,name')
            ->where('listing_id', $listing->id)
            ->where('is_approved', true)
            ->latest()
            ->get();

        $avg = $reviews->avg('rating');

        return response()->json([
            'status' => 'success',
            'data' => $reviews,
            'average_rating' => round($avg ?: 5.0, 1),
            'total_reviews' => $reviews->count(),
        ]);
    }

    public function store(Request $request, Listing $listing): JsonResponse
    {
        $user = $request->user();

        // Cannot review own listing
        if ($listing->user_id === $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot review your own listing.',
            ], 422);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = Review::updateOrCreate(
            [
                'user_id' => $user->id,
                'listing_id' => $listing->id,
            ],
            [
                'seller_id' => $listing->user_id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'is_approved' => true,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Review submitted successfully.',
            'data' => $review->load('user:id,name'),
        ], 201);
    }
}
