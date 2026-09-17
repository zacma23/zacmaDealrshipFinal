<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectListingRequest;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\ListingStatusUpdatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AdminController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                $user = $request->user();
                if (!$user || !$user->isSuperAdmin()) {
                    abort(403, 'Unauthorized. Super Admin privileges required.');
                }
                return $next($request);
            },
        ];
    }

    public function dashboard(): JsonResponse
    {
        $totalUsers = User::count();
        $totalListings = Listing::count();

        $listingsByType = [
            'vehicle' => Listing::where('type', Listing::TYPE_VEHICLE)->count(),
            'real_estate' => Listing::where('type', Listing::TYPE_REAL_ESTATE)->count(),
            'apartment' => Listing::where('type', Listing::TYPE_APARTMENT)->count(),
        ];

        $pendingApprovalsCount = Listing::where('status', Listing::STATUS_PENDING)->count();
        $activeSubscriptionsCount = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('ends_at', '>', now())
            ->count();

        // Revenue this month in ETB
        $revenueThisMonth = (float) Payment::where('status', Payment::STATUS_COMPLETED)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        // Recent pending listings
        $recentPending = Listing::with(['user.profile', 'category', 'primaryImage'])
            ->where('status', Listing::STATUS_PENDING)
            ->latest()
            ->take(5)
            ->get();

        // Recent payments
        $recentPayments = Payment::with(['user', 'plan'])
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'metrics' => [
                    'total_users' => $totalUsers,
                    'total_listings' => $totalListings,
                    'listings_by_type' => $listingsByType,
                    'pending_approvals_count' => $pendingApprovalsCount,
                    'active_subscriptions' => $activeSubscriptionsCount,
                    'revenue_this_month_etb' => $revenueThisMonth,
                ],
                'recent_pending' => $recentPending,
                'recent_payments' => $recentPayments,
            ],
        ]);
    }

    // ----------------------------------------------------
    // User Management
    // ----------------------------------------------------

    public function users(Request $request): JsonResponse
    {
        $query = User::with('profile');

        if ($request->filled('search')) {
            $s = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                  ->orWhere('email', 'like', $s)
                  ->orWhere('phone', 'like', $s);
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $users = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    public function toggleUserStatus(User $user): JsonResponse
    {
        if ($user->isSuperAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot suspend a Super Admin account.',
            ], 422);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'status' => 'success',
            'message' => $user->is_active ? 'User account activated.' : 'User account suspended.',
            'data' => [
                'is_active' => $user->is_active,
            ],
        ]);
    }

    // ----------------------------------------------------
    // Listing Approvals Queue
    // ----------------------------------------------------

    public function pendingListings(Request $request): JsonResponse
    {
        $query = Listing::with(['user.profile', 'category', 'images', 'primaryImage'])
            ->where('status', Listing::STATUS_PENDING);

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $listings = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $listings,
        ]);
    }

    public function approveListing(Listing $listing): JsonResponse
    {
        $listing->update([
            'status' => Listing::STATUS_PUBLISHED,
            'published_at' => now(),
            'rejection_reason' => null,
        ]);

        // Notify user
        try {
            $listing->user?->notify(new ListingStatusUpdatedNotification(
                $listing,
                Listing::STATUS_PUBLISHED
            ));
        } catch (\Throwable $e) {
            Log::warning('Listing approved notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Listing approved and published.',
            'data' => $listing,
        ]);
    }

    public function rejectListing(RejectListingRequest $request, Listing $listing): JsonResponse
    {
        $validated = $request->validated();

        $listing->update([
            'status' => Listing::STATUS_REJECTED,
            'rejection_reason' => $validated['reason'],
        ]);

        // Notify user
        try {
            $listing->user?->notify(new ListingStatusUpdatedNotification(
                $listing,
                Listing::STATUS_REJECTED,
                $validated['reason']
            ));
        } catch (\Throwable $e) {
            Log::warning('Listing rejected notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Listing rejected with reason provided to seller.',
            'data' => $listing,
        ]);
    }

    // ----------------------------------------------------
    // Category Management (Flat list)
    // ----------------------------------------------------

    public function categories(): JsonResponse
    {
        $categories = Category::withCount('listings')->orderBy('type')->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:vehicle,real_estate,apartment',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(4),
            'type' => $validated['type'],
            'icon' => $validated['icon'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully.',
            'data' => $category,
        ], 201);
    }

    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|in:vehicle,real_estate,apartment',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Category updated successfully.',
            'data' => $category,
        ]);
    }

    // ----------------------------------------------------
    // Subscription Plans Management
    // ----------------------------------------------------

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::withCount('subscriptions')->get();

        return response()->json([
            'status' => 'success',
            'data' => $plans,
        ]);
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'listing_limit' => 'sometimes|required|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $plan->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Subscription plan settings updated.',
            'data' => $plan,
        ]);
    }

    // ----------------------------------------------------
    // All Transactions
    // ----------------------------------------------------

    public function transactions(Request $request): JsonResponse
    {
        $query = Payment::with(['user.profile', 'plan', 'subscription']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->input('provider'));
        }

        $transactions = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $transactions,
        ]);
    }

    // ----------------------------------------------------
    // Payment Gateway Settings (Chapa)
    // ----------------------------------------------------

    public function gatewaySettings(): JsonResponse
    {
        $enabled = Setting::get('chapa_enabled', 'true') === 'true';
        $mode = Setting::get('chapa_mode', 'test'); // test or live
        $publicKey = Setting::get('chapa_public_key', env('CHAPA_PUBLIC_KEY', 'CHAPUBK_TEST-xxxx'));
        $hasSecret = !empty(Setting::get('chapa_secret_key', env('CHAPA_SECRET_KEY')));

        return response()->json([
            'status' => 'success',
            'data' => [
                'provider' => 'chapa',
                'name' => 'Chapa (Ethiopia)',
                'enabled' => $enabled,
                'mode' => $mode,
                'public_key' => $publicKey,
                'has_secret_key' => $hasSecret,
            ],
        ]);
    }

    public function updateGatewaySettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'mode' => 'required|string|in:test,live',
            'public_key' => 'nullable|string',
            'secret_key' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
        ]);

        Setting::set('chapa_enabled', $validated['enabled'] ? 'true' : 'false');
        Setting::set('chapa_mode', $validated['mode']);

        if (!empty($validated['public_key'])) {
            Setting::set('chapa_public_key', $validated['public_key']);
        }
        if (!empty($validated['secret_key'])) {
            Setting::set('chapa_secret_key', $validated['secret_key']);
        }
        if (!empty($validated['webhook_secret'])) {
            Setting::set('chapa_webhook_secret', $validated['webhook_secret']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Chapa payment gateway settings updated successfully.',
        ]);
    }
}
