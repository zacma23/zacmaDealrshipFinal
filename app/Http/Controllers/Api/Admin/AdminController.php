<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectListingRequest;
use App\Models\Category;
use App\Models\Listing;
use App\Models\PackageUpgradeRequest;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\ListingStatusUpdatedNotification;
use App\Notifications\PackageUpgradeApprovedNotification;
use App\Notifications\PackageUpgradeRejectedNotification;
use App\Notifications\SubscriptionActivatedNotification;
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
        $pendingUpgradesCount = PackageUpgradeRequest::where('approval_status', PackageUpgradeRequest::APPROVAL_PENDING)->count();
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
                    'pending_upgrades_count' => $pendingUpgradesCount,
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
    // Payment Gateway Management (Multi-Provider)
    // ----------------------------------------------------

    /**
     * List all payment gateways with masked credentials
     */
    public function gateways(): JsonResponse
    {
        $gateways = PaymentGateway::orderBy('code')->get()->map(function ($gw) {
            return $this->formatGatewayForResponse($gw);
        });

        return response()->json([
            'status' => 'success',
            'data'   => $gateways,
        ]);
    }

    /**
     * Update a payment gateway's settings (credentials stored as JSON, never returned raw)
     */
    public function updateGateway(Request $request, PaymentGateway $gateway): JsonResponse
    {
        $validated = $request->validate([
            'is_active'       => 'nullable|boolean',
            'is_test_mode'    => 'nullable|boolean',
            'api_key'         => 'nullable|string|max:500',
            'secret_key'      => 'nullable|string|max:500',
            'public_key'      => 'nullable|string|max:500',
            'merchant_id'     => 'nullable|string|max:200',
            'account_number'  => 'nullable|string|max:200',
            'webhook_url'     => 'nullable|url|max:500',
            'webhook_secret'  => 'nullable|string|max:500',
        ]);

        // Merge credentials into the existing JSON blob (don't expose old values)
        $existingCreds = $gateway->credentials ?? [];

        $credentialFields = ['api_key', 'secret_key', 'public_key', 'merchant_id', 'account_number', 'webhook_url', 'webhook_secret'];
        $updatedCreds = $existingCreds;

        foreach ($credentialFields as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] !== null && $validated[$field] !== '') {
                $updatedCreds[$field] = $validated[$field];
            }
        }

        $updates = ['credentials' => $updatedCreds];

        if (array_key_exists('is_active', $validated) && $validated['is_active'] !== null) {
            $updates['is_active'] = $validated['is_active'];
        }
        if (array_key_exists('is_test_mode', $validated) && $validated['is_test_mode'] !== null) {
            $updates['is_test_mode'] = $validated['is_test_mode'];
        }

        $gateway->update($updates);

        return response()->json([
            'status'  => 'success',
            'message' => "{$gateway->name} settings updated successfully.",
            'data'    => $this->formatGatewayForResponse($gateway->fresh()),
        ]);
    }

    /**
     * Toggle a payment gateway enabled/disabled
     */
    public function toggleGateway(PaymentGateway $gateway): JsonResponse
    {
        $gateway->update(['is_active' => !$gateway->is_active]);

        return response()->json([
            'status'  => 'success',
            'message' => $gateway->is_active ? "{$gateway->name} enabled." : "{$gateway->name} disabled.",
            'data'    => $this->formatGatewayForResponse($gateway->fresh()),
        ]);
    }

    /**
     * Format gateway data for API response — mask all credential values
     */
    private function formatGatewayForResponse(PaymentGateway $gw): array
    {
        $creds = $gw->credentials ?? [];
        $maskedCreds = [];

        foreach ($creds as $key => $value) {
            if ($key === 'webhook_url') {
                $maskedCreds[$key] = $value; // URL is safe to expose
            } else {
                $maskedCreds[$key] = !empty($value) ? '••••••' . substr($value, -4) : null;
            }
        }

        return [
            'id'            => $gw->id,
            'code'          => $gw->code,
            'name'          => $gw->name,
            'is_active'     => $gw->is_active,
            'is_test_mode'  => $gw->is_test_mode,
            'credentials'   => $maskedCreds,
            'has_api_key'   => !empty($creds['api_key'] ?? null),
            'has_secret'    => !empty($creds['secret_key'] ?? null),
            'has_public_key'=> !empty($creds['public_key'] ?? null),
            'webhook_url'   => $creds['webhook_url'] ?? null,
            'updated_at'    => $gw->updated_at?->toIso8601String(),
        ];
    }

    // Keep legacy gateway settings endpoint for backward compatibility
    public function gatewaySettings(): JsonResponse
    {
        $enabled = Setting::get('chapa_enabled', 'true') === 'true';
        $mode = Setting::get('chapa_mode', 'test');
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

    // ----------------------------------------------------
    // Package Upgrade Requests & Verification
    // ----------------------------------------------------

    public function upgradeRequests(Request $request): JsonResponse
    {
        $query = PackageUpgradeRequest::with([
            'user.profile',
            'requestedPlan',
            'currentPlan',
            'payment',
            'approvedBy',
            'rejectedBy',
        ]);

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->input('approval_status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        $requests = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $requests,
        ]);
    }

    public function approveUpgradeRequest(PackageUpgradeRequest $upgradeRequest): JsonResponse
    {
        if ($upgradeRequest->approval_status === PackageUpgradeRequest::APPROVAL_APPROVED) {
            return response()->json([
                'status' => 'error',
                'message' => 'This upgrade request has already been approved.',
            ], 422);
        }

        DB::transaction(function () use ($upgradeRequest) {
            // 1. Deactivate existing active subscriptions
            Subscription::where('user_id', $upgradeRequest->user_id)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update(['status' => Subscription::STATUS_EXPIRED]);

            // 2. Determine duration based on billing cycle
            $durationDays = match ($upgradeRequest->billing_cycle) {
                'yearly' => 365,
                'quarterly' => 90,
                default => 30,
            };

            // 3. Create active subscription
            $subscription = Subscription::create([
                'user_id' => $upgradeRequest->user_id,
                'plan_id' => $upgradeRequest->requested_plan_id,
                'status' => Subscription::STATUS_ACTIVE,
                'billing_cycle' => $upgradeRequest->billing_cycle,
                'gateway' => $upgradeRequest->payment_gateway,
                'starts_at' => now(),
                'ends_at' => now()->addDays($durationDays),
            ]);

            // 4. Update payment if exists
            if ($upgradeRequest->payment) {
                $upgradeRequest->payment->update([
                    'subscription_id' => $subscription->id,
                    'status' => Payment::STATUS_COMPLETED,
                    'verified_at' => $upgradeRequest->payment->verified_at ?? now(),
                ]);
            }

            // 5. Update upgrade request
            $upgradeRequest->update([
                'approval_status' => PackageUpgradeRequest::APPROVAL_APPROVED,
                'payment_status' => PackageUpgradeRequest::PAYMENT_VERIFIED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // 6. Notify user
            try {
                $upgradeRequest->user?->notify(new PackageUpgradeApprovedNotification($upgradeRequest));
                $upgradeRequest->user?->notify(new SubscriptionActivatedNotification($subscription));
            } catch (\Throwable $e) {
                Log::warning('Upgrade approval notification failed: ' . $e->getMessage());
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Package upgrade approved and activated successfully. Client now has access.',
            'data' => $upgradeRequest->fresh([
                'user.profile',
                'requestedPlan',
                'currentPlan',
                'payment',
                'approvedBy',
            ]),
        ]);
    }

    public function rejectUpgradeRequest(Request $request, PackageUpgradeRequest $upgradeRequest): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        if ($upgradeRequest->approval_status === PackageUpgradeRequest::APPROVAL_APPROVED) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot reject an already approved upgrade request.',
            ], 422);
        }

        $upgradeRequest->update([
            'approval_status' => PackageUpgradeRequest::APPROVAL_REJECTED,
            'rejection_reason' => $validated['reason'],
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        try {
            $upgradeRequest->user?->notify(new PackageUpgradeRejectedNotification($upgradeRequest, $validated['reason']));
        } catch (\Throwable $e) {
            Log::warning('Upgrade rejection notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Package upgrade request rejected with reason provided to client.',
            'data' => $upgradeRequest->fresh([
                'user.profile',
                'requestedPlan',
                'currentPlan',
                'rejectedBy',
            ]),
        ]);
    }

    public function verifyUpgradePayment(PackageUpgradeRequest $upgradeRequest): JsonResponse
    {
        DB::transaction(function () use ($upgradeRequest) {
            if ($upgradeRequest->payment) {
                $upgradeRequest->payment->update([
                    'status' => Payment::STATUS_COMPLETED,
                    'verified_at' => now(),
                ]);
            }

            $upgradeRequest->update([
                'payment_status' => PackageUpgradeRequest::PAYMENT_VERIFIED,
                'paid_at' => $upgradeRequest->paid_at ?? now(),
            ]);

            try {
                $upgradeRequest->user?->notify(new \App\Notifications\PackageUpgradePaymentReceivedNotification($upgradeRequest));
            } catch (\Throwable $e) {
                Log::warning('Payment verification notification failed: ' . $e->getMessage());
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Payment verified successfully. Awaiting final upgrade approval.',
            'data' => $upgradeRequest->fresh(['payment', 'user']),
        ]);
    }
}


