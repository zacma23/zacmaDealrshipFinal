<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionActivatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(protected PaymentGatewayInterface $defaultGateway)
    {
    }

    public function resolveGateway(?string $provider = 'chapa'): PaymentGatewayInterface
    {
        return match (strtolower($provider ?? 'chapa')) {
            'telebirr' => app(TelebirrPaymentGateway::class),
            'cbe', 'cbebirr' => app(CbePaymentGateway::class),
            'ebirr' => app(EbirrPaymentGateway::class),
            'santimpay' => app(SantimPayPaymentGateway::class),
            default => $this->defaultGateway,
        };
    }

    public function initiatePlanSubscription(
        User $user,
        SubscriptionPlan $plan,
        ?string $returnUrl = null,
        string $gatewayName = 'chapa',
        string $billingCycle = 'monthly',
        ?int $upgradeRequestId = null
    ): array {
        $reference = 'TX-ZACMA-' . strtoupper(Str::random(12));

        // Calculate amount based on billing cycle
        $amount = match ($billingCycle) {
            'yearly' => $plan->price_yearly ?: ($plan->price * 10), // 2 months free
            'quarterly' => $plan->price_quarterly ?: ($plan->price * 2.7), // 10% discount
            default => $plan->price,
        };

        $payment = Payment::create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'provider' => $gatewayName,
            'amount' => $amount,
            'currency' => $plan->currency ?: 'ETB',
            'transaction_reference' => $reference,
            'status' => Payment::STATUS_PENDING,
            'request_payload' => [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'user_email' => $user->email,
                'billing_cycle' => $billingCycle,
                'gateway' => $gatewayName,
                'upgrade_request_id' => $upgradeRequestId,
            ],
        ]);

        $callbackUrl = route('api.payment.webhook', ['provider' => $gatewayName]);
        $defaultReturnUrl = $returnUrl ?: url('/dashboard/subscriptions?status=success&ref=' . $reference);

        $customerData = [
            'email' => $user->email,
            'first_name' => $user->name,
            'phone' => $user->phone,
        ];

        $gateway = $this->resolveGateway($gatewayName);
        $initResult = $gateway->initializePayment($payment, $customerData, $defaultReturnUrl, $callbackUrl);

        return [
            'payment' => $payment,
            'checkout_url' => $initResult['checkout_url'] ?? null,
            'reference' => $reference,
            'status' => $initResult['status'] ?? 'pending',
            'gateway' => $gatewayName,
            'billing_cycle' => $billingCycle,
        ];
    }

    /**
     * Process webhook or payment callback with strict idempotency.
     */
    public function processWebhookPayment(string $reference, array $payload, ?string $idempotencyKey = null): array
    {
        return DB::transaction(function () use ($reference, $payload, $idempotencyKey) {
            $payment = Payment::where('transaction_reference', $reference)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                Log::warning("Payment not found for reference {$reference}");
                return [
                    'success' => false,
                    'message' => 'Payment reference not found',
                ];
            }

            // IDEMPOTENCY CHECK:
            // If already completed/verified, return immediately to prevent duplicate subscription extension
            if ($payment->isVerified()) {
                Log::info("Payment {$reference} already processed. Skipping duplicate activation (Idempotent).");
                return [
                    'success' => true,
                    'idempotent' => true,
                    'message' => 'Payment already verified',
                    'payment' => $payment,
                ];
            }

            // Mark completed
            // Mark payment completed
            $payment->update([
                'status' => Payment::STATUS_COMPLETED,
                'provider_reference' => $payload['reference'] ?? $payload['id'] ?? null,
                'response_payload' => $payload,
                'verified_at' => now(),
                'idempotency_key' => $idempotencyKey ?: ($payload['idempotency_key'] ?? $reference . '-webhook'),
            ]);

            // Activate or extend subscription
            // CHECK FOR PACKAGE UPGRADE REQUEST:
            // If this payment is associated with a Package Upgrade Request, update the upgrade request's
            // payment status to 'paid' but DO NOT activate the package immediately.
            // Admin must review and approve the upgrade before package activation.
            $upgradeRequest = \App\Models\PackageUpgradeRequest::where('payment_id', $payment->id)
                ->orWhere('payment_reference', $reference)
                ->orWhere('id', $payment->request_payload['upgrade_request_id'] ?? 0)
                ->first();

            if ($upgradeRequest) {
                $upgradeRequest->update([
                    'payment_status' => \App\Models\PackageUpgradeRequest::PAYMENT_PAID,
                    'paid_at' => now(),
                ]);

                // Notify user: Payment received – Awaiting Admin Approval
                try {
                    $payment->user?->notify(new \App\Notifications\PackageUpgradePaymentReceivedNotification($upgradeRequest));
                } catch (\Throwable $e) {
                    Log::warning('Payment received notification failed: ' . $e->getMessage());
                }

                return [
                    'success' => true,
                    'idempotent' => false,
                    'message' => 'Payment received – Awaiting Admin Approval',
                    'payment' => $payment,
                    'upgrade_request' => $upgradeRequest,
                ];
            }

            // Standard / Legacy direct activation (when no upgrade request approval workflow is attached)
            $plan = $payment->plan ?: SubscriptionPlan::find($payment->plan_id);
            if ($plan && $payment->user) {
                $user = $payment->user;

                // Deactivate any existing active subscriptions
                Subscription::where('user_id', $user->id)
                    ->where('status', Subscription::STATUS_ACTIVE)
                    ->update(['status' => Subscription::STATUS_EXPIRED]);

                // Create active 30-day subscription
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'status' => Subscription::STATUS_ACTIVE,
                    'starts_at' => now(),
                    'ends_at' => now()->addDays(30),
                ]);

                $payment->update(['subscription_id' => $subscription->id]);

                // Dispatch notification
                try {
                    $user->notify(new SubscriptionActivatedNotification($subscription));
                } catch (\Throwable $e) {
                    Log::warning('Subscription notification failed: ' . $e->getMessage());
                }

                return [
                    'success' => true,
                    'idempotent' => false,
                    'message' => 'Subscription activated successfully',
                    'payment' => $payment,
                    'subscription' => $subscription,
                ];
            }

            return [
                'success' => true,
                'message' => 'Payment verified successfully',
                'payment' => $payment,
            ];
        });
    }

    public function getGateway(?string $provider = null): PaymentGatewayInterface
    {
        return $provider ? $this->resolveGateway($provider) : $this->defaultGateway;
    }
}

