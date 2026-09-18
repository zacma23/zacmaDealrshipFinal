<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PackageUpgradeRequest;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Notifications\PackageUpgradeRequestSubmittedNotification;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    public function plans(Request $request): JsonResponse
    {
        $user = $request->user();
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('price')->get();

        $activeSub = $user?->activeSubscription;
        $currentPlanId = $activeSub?->plan_id;

        $plansData = $plans->map(function ($plan) use ($currentPlanId) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price' => (float) $plan->price,
                'price_quarterly' => (float) ($plan->price_quarterly ?: ($plan->price * 2.7)),
                'price_yearly' => (float) ($plan->price_yearly ?: ($plan->price * 10)),
                'currency' => $plan->currency ?: 'ETB',
                'listing_limit' => $plan->listing_limit,
                'billing_period' => $plan->billing_period,
                'features' => $plan->features ?: [],
                'is_current' => $currentPlanId === $plan->id || (!$currentPlanId && $plan->slug === 'basic'),
            ];
        });

        // Check if user has an active upgrade request (pending approval or pending payment)
        $latestUpgradeRequest = null;
        if ($user) {
            $latestUpgradeRequest = PackageUpgradeRequest::with(['requestedPlan', 'currentPlan', 'payment'])
                ->where('user_id', $user->id)
                ->latest()
                ->first();
        }

        return response()->json([
            'status' => 'success',
            'data' => $plansData,
            'available_gateways' => [
                ['id' => 'chapa', 'name' => 'Chapa (Cards, Telebirr, CBEBirr)'],
                ['id' => 'telebirr', 'name' => 'Telebirr Direct'],
                ['id' => 'cbe', 'name' => 'CBE Birr'],
                ['id' => 'ebirr', 'name' => 'eBirr'],
                ['id' => 'santimpay', 'name' => 'SantimPay'],
            ],
            'current_subscription' => $activeSub ? [
                'id' => $activeSub->id,
                'plan_name' => $activeSub->plan?->name,
                'listing_limit' => $activeSub->plan?->listing_limit,
                'billing_cycle' => $activeSub->billing_cycle ?? 'monthly',
                'gateway' => $activeSub->gateway ?? 'chapa',
                'ends_at' => $activeSub->ends_at->toIso8601String(),
                'days_left' => max(0, (int) now()->diffInDays($activeSub->ends_at, false)),
            ] : null,
            'pending_upgrade_request' => $latestUpgradeRequest ? [
                'id' => $latestUpgradeRequest->id,
                'current_plan_name' => $latestUpgradeRequest->currentPlan?->name ?? 'Basic',
                'requested_plan_name' => $latestUpgradeRequest->requestedPlan?->name,
                'requested_plan_id' => $latestUpgradeRequest->requested_plan_id,
                'price' => (float) $latestUpgradeRequest->price,
                'currency' => $latestUpgradeRequest->currency,
                'billing_cycle' => $latestUpgradeRequest->billing_cycle,
                'payment_gateway' => $latestUpgradeRequest->payment_gateway,
                'payment_reference' => $latestUpgradeRequest->payment_reference,
                'payment_url' => $latestUpgradeRequest->payment_url,
                'payment_status' => $latestUpgradeRequest->payment_status,
                'approval_status' => $latestUpgradeRequest->approval_status,
                'created_at' => $latestUpgradeRequest->created_at->toIso8601String(),
                'paid_at' => $latestUpgradeRequest->paid_at?->toIso8601String(),
                'rejection_reason' => $latestUpgradeRequest->rejection_reason,
            ] : null,
        ]);
    }

    /**
     * Client Package Upgrade Request with Payment Link Generation.
     * The requested package does NOT become active immediately.
     */
    public function requestUpgrade(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'gateway' => 'nullable|string|in:chapa,telebirr,cbe,ebirr,santimpay',
            'billing_cycle' => 'nullable|string|in:monthly,quarterly,yearly',
            'return_url' => 'nullable|url',
        ]);

        $user = $request->user();
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $gateway = $validated['gateway'] ?? 'chapa';
        $billingCycle = $validated['billing_cycle'] ?? 'monthly';

        // If it's free basic plan
        // Basic plan is free default
        if ((float) $plan->price <= 0) {
            return response()->json([
                'status' => 'info',
                'message' => 'Basic plan is the free default plan.',
            ]);
        }

        // Calculate amount based on billing cycle
        $amount = match ($billingCycle) {
            'yearly' => $plan->price_yearly ?: ($plan->price * 10),
            'quarterly' => $plan->price_quarterly ?: ($plan->price * 2.7),
            default => $plan->price,
        };

        // 1. Create the Upgrade Request record (approval_status: pending, payment_status: pending)
        $upgradeRequest = PackageUpgradeRequest::create([
            'user_id' => $user->id,
            'current_plan_id' => $user->activeSubscription?->plan_id,
            'requested_plan_id' => $plan->id,
            'price' => $amount,
            'currency' => $plan->currency ?: 'ETB',
            'billing_cycle' => $billingCycle,
            'payment_gateway' => $gateway,
            'payment_status' => PackageUpgradeRequest::PAYMENT_PENDING,
            'approval_status' => PackageUpgradeRequest::APPROVAL_PENDING,
        ]);

        // 2. Generate Payment Link using existing payment integration
        $result = $this->paymentService->initiatePlanSubscription(
            $user,
            $plan,
            $validated['return_url'] ?? null,
            $gateway,
            $billingCycle,
            $upgradeRequest->id
        );

        // Update upgrade request with payment details
        $upgradeRequest->update([
            'payment_id' => $result['payment']->id,
            'payment_reference' => $result['reference'],
            'payment_url' => $result['checkout_url'],
        ]);

        // 3. Notify user: Upgrade request submitted and payment link generated
        try {
            $user->notify(new PackageUpgradeRequestSubmittedNotification($upgradeRequest));
        } catch (\Throwable $e) {
            Log::warning('Upgrade request notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Package upgrade request created. Please proceed with payment.',
            'upgrade_request' => [
                'id' => $upgradeRequest->id,
                'current_plan' => $upgradeRequest->currentPlan?->name ?? 'Basic',
                'requested_plan' => $plan->name,
                'price' => (float) $amount,
                'currency' => $plan->currency ?: 'ETB',
                'billing_cycle' => $billingCycle,
                'payment_gateway' => $gateway,
                'payment_status' => $upgradeRequest->payment_status,
                'approval_status' => $upgradeRequest->approval_status,
                'payment_reference' => $result['reference'],
                'checkout_url' => $result['checkout_url'],
            ],
            // Compatible fields with existing checkout schema
            'checkout_url' => $result['checkout_url'],
            'reference' => $result['reference'],
            'gateway' => $gateway,
            'billing_cycle' => $billingCycle,
            'amount' => (float) $result['payment']->amount,
            'currency' => $plan->currency ?: 'ETB',
        ]);
    }

    /**
     * Standard checkout route - aliases to requestUpgrade to ensure consistent workflow.
     */
    public function checkout(Request $request): JsonResponse
    {
        return $this->requestUpgrade($request);
    }

    /**
     * Get current user's latest upgrade request status.
     */
    public function currentUpgradeRequest(Request $request): JsonResponse
    {
        $user = $request->user();

        $upgradeRequest = PackageUpgradeRequest::with(['requestedPlan', 'currentPlan', 'payment', 'approvedBy'])
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $upgradeRequest,
        ]);
    }

    /**
     * Get current user's package upgrade request history.
     */
    public function upgradeHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        $history = PackageUpgradeRequest::with(['requestedPlan', 'currentPlan', 'payment', 'approvedBy'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $history,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $payments = Payment::with(['plan', 'subscription'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $payments,
        ]);
    }
}

