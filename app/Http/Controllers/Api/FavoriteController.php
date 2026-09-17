<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $favorites = Listing::whereHas('favorites', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->with(['category', 'primaryImage', 'images', 'user.profile'])
            ->published()
            ->latest()
            ->paginate($request->input('per_page', 12));

        return response()->json([
            'status' => 'success',
            'data' => $favorites,
        ]);
    }

    public function toggle(Listing $listing, Request $request): JsonResponse
    {
        $user = $request->user();

        $favorite = Favorite::where('user_id', $user->id)
            ->where('listing_id', $listing->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $isFavorited = false;
            $message = 'Listing removed from favorites.';
        } else {
            Favorite::create([
                'user_id' => $user->id,
                'listing_id' => $listing->id,
            ]);
            $isFavorited = true;
            $message = 'Listing added to favorites.';
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'is_favorited' => $isFavorited,
        ]);
    }
}

