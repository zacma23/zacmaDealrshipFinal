<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(string $username): JsonResponse
    {
        $user = User::with('profile')
            ->where('username', $username)
            ->orWhere('id', is_numeric($username) ? (int)$username : null)
            ->firstOrFail();

        // Get active published listings for this user
        $listings = Listing::with(['category', 'primaryImage'])
            ->where('user_id', $user->id)
            ->published()
            ->latest()
            ->paginate(12);

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar' => $user->getAvatarUrl(),
                    'city' => $user->profile?->city ?? 'Addis Ababa',
                    'bio' => $user->profile?->bio,
                    'phone' => $user->phone,
                    'is_verified' => (bool) $user->profile?->is_verified,
                    'member_since' => $user->created_at?->format('M Y'),
                ],
                'listings' => $listings,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'unauthenticated'], 401);
        }

        $user->load(['profile', 'activeSubscription.plan', 'roles']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->getAvatarUrl(),
                'role' => $user->role,
                'is_super_admin' => $user->isSuperAdmin(),
                'profile' => $user->profile,
                'quota' => [
                    'limit' => $user->getListingLimit(),
                    'used' => $user->getActiveListingCount(),
                    'remaining' => max(0, $user->getListingLimit() - $user->getActiveListingCount()),
                    'can_create' => $user->canCreateListing(),
                ],
                'subscription' => $user->activeSubscription ? [
                    'plan_name' => $user->activeSubscription->plan?->name,
                    'ends_at' => $user->activeSubscription->ends_at->toIso8601String(),
                ] : [
                    'plan_name' => 'Basic',
                    'ends_at' => null,
                ],
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:50|alpha_dash|unique:users,username,' . $user->id,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'city' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'account_type' => 'nullable|string|in:individual,business,dealer',
            'business_name' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:100',
            'tin_number' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'] ?? $user->username,
            'phone' => $validated['phone'] ?? $user->phone,
        ]);

        $profile = $user->profile ?: $user->profile()->create(['user_id' => $user->id]);

        $photoPath = $profile->photo;
        if ($request->hasFile('photo')) {
            if ($photoPath && !str_starts_with($photoPath, 'http')) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $request->file('photo')->store('profiles', 'public');
        }

        $profile->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? $user->phone,
            'city' => $validated['city'] ?? $profile->city,
            'bio' => $validated['bio'] ?? $profile->bio,
            'photo' => $photoPath,
            'account_type' => $validated['account_type'] ?? $profile->account_type,
            'business_name' => $validated['business_name'] ?? $profile->business_name,
            'license_number' => $validated['license_number'] ?? $profile->license_number,
            'tin_number' => $validated['tin_number'] ?? $profile->tin_number,
            'address' => $validated['address'] ?? $profile->address,
            'website' => $validated['website'] ?? $profile->website,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
            'data' => [
                'user' => $user->fresh(['profile']),
            ],
        ]);
    }
}

