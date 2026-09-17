<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        ]);
    }

    public function checkout(Request $request): JsonResponse
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
        if ((float) $plan->price <= 0) {
            return response()->json([
                'status' => 'info',
                'message' => 'Basic plan is the free default plan.',
            ]);
        }

        $result = $this->paymentService->initiatePlanSubscription(
            $user,
            $plan,
            $validated['return_url'] ?? null,
            $gateway,
            $billingCycle
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Checkout initialized.',
            'checkout_url' => $result['checkout_url'],
            'reference' => $result['reference'],
            'gateway' => $gateway,
            'billing_cycle' => $billingCycle,
            'amount' => (float) $result['payment']->amount,
            'currency' => $plan->currency ?: 'ETB',
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

