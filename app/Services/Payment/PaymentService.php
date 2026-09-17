<?php

namespace App\Services\Payment;

use App\Models\AuditLog;
use App\Models\AutomationRule;
use App\Models\CrmActivity;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Automation\AutomationService;
use Exception;
use Illuminate\Support\Str;

class PaymentService
{
    protected array $providers = [];

    public function __construct()
    {
        $this->registerProvider(new SantimPayProvider);
        $this->registerProvider(new TelebirrProvider);
        $this->registerProvider(new ChapaProvider);
        $this->registerProvider(new PayPalProvider);
        $this->registerProvider(new CardProvider);
        $this->registerProvider(new CryptoProvider);
        $this->registerProvider(new CashSandboxProvider);
    }

    public function registerProvider(PaymentProviderInterface $provider): void
    {
        $this->providers[$provider->getProviderName()] = $provider;
    }

    public function getProvider(string $name): PaymentProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new Exception("Payment provider [{$name}] is not registered.");
        }
        return $this->providers[$name];
    }

    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }

    public function createPaymentForOrder(Order $order, string $providerName, array $options = []): array
    {
        $provider = $this->getProvider($providerName);

        $reference = 'PAY-' . strtoupper(Str::random(6)) . '-' . time();

        $payment = Payment::create([
            'organization_id' => $order->organization_id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => $providerName,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'transaction_reference' => $reference,
            'status' => Payment::STATUS_PENDING,
            'request_payload' => $options,
        ]);

        $result = $provider->initiatePayment($payment, $options);

        if ($result['success'] && !empty($result['provider_reference'])) {
            $payment->update([
                'provider_reference' => $result['provider_reference'],
                'response_payload' => $result['raw'] ?? null,
            ]);
        }

        return [
            'payment' => $payment,
            'redirect_url' => $result['redirect_url'] ?? null,
            'success' => $result['success'],
        ];
    }

    public function verifyPayment(string $providerName, string $reference): bool
    {
        $payment = Payment::where('transaction_reference', $reference)
            ->orWhere('provider_reference', $reference)
            ->first();

        if (!$payment) {
            return false;
        }

        if ($payment->isVerified()) {
            return true;
        }

        $provider = $this->getProvider($providerName);
        $verified = $provider->verifyPayment($payment);

        if ($verified) {
            $this->finalizeSuccessfulPayment($payment);
            return true;
        }

        return false;
    }

    public function finalizeSuccessfulPayment(Payment $payment): void
    {
        if ($payment->status === Payment::STATUS_VERIFIED) {
            return;
        }

        $payment->update([
            'status' => Payment::STATUS_VERIFIED,
            'verified_at' => now(),
        ]);

        $order = $payment->order;
        if ($order) {
            $order->update([
                'payment_status' => Order::PAYMENT_PAID,
                'status' => Order::STATUS_CONFIRMED,
            ]);

            // 1. Create Invoice
            Invoice::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'organization_id' => $order->organization_id,
                    'payment_id' => $payment->id,
                    'invoice_number' => 'INV-' . strtoupper(Str::random(4)) . '-' . str_pad($order->id, 5, '0', STR_PAD_LEFT),
                    'invoice_date' => now(),
                    'due_date' => now()->addDays(7),
                    'subtotal' => $order->total_amount,
                    'tax' => 0.00,
                    'total' => $order->total_amount,
                    'currency' => $order->currency,
                    'status' => 'paid',
                ]
            );

            // 2. CRM Activity Log on Contact Timeline
            if ($order->contact_id) {
                CrmActivity::create([
                    'organization_id' => $order->organization_id,
                    'contact_id' => $order->contact_id,
                    'user_id' => $payment->user_id,
                    'type' => CrmActivity::TYPE_PAYMENT,
                    'subject' => "Payment received: {$order->currency} {$order->total_amount}",
                    'description' => "Verified payment via {$payment->provider} for Order #{$order->order_number}",
                    'metadata' => [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                        'reference' => $payment->transaction_reference,
                    ],
                    'occurred_at' => now(),
                ]);
            }

            // 3. If Order is for Featured Listing promotion
            if ($order->purchase_type === Order::TYPE_FEATURED_LISTING && $order->listing_id) {
                $listing = $order->listing;
                if ($listing) {
                    $listing->update([
                        'featured' => true,
                        'featured_until' => now()->addDays(30),
                    ]);
                }
            }

            // 4. If Order is for Organization Subscription Plan
            if ($order && $order->purchase_type === Order::TYPE_SUBSCRIPTION) {
                $planId = $order->metadata['plan_id'] ?? null;
                $plan = $planId ? \App\Models\SubscriptionPlan::find($planId) : null;

                if (!$plan && !empty($order->notes) && preg_match('/upgrade to (.*)/i', $order->notes, $m)) {
                    $plan = \App\Models\SubscriptionPlan::where('name', trim($m[1]))->first();
                }
                if (!$plan) {
                    $plan = \App\Models\SubscriptionPlan::where('price', $order->total_amount)->first();
                }

                if ($plan && $order->organization_id) {
                    $org = \App\Models\Organization::find($order->organization_id);
                    if ($org) {
                        $org->update([
                            'subscription_plan_id' => $plan->id,
                            'subscription_ends_at' => now()->addMonth(),
                        ]);

                        \App\Models\Subscription::create([
                            'organization_id' => $org->id,
                            'subscription_plan_id' => $plan->id,
                            'status' => 'active',
                            'current_period_starts_at' => now(),
                            'current_period_ends_at' => now()->addMonth(),
                        ]);
                    }
                }
            }

            // 4. Trigger Automation Rule
            app(AutomationService::class)->handleTrigger(
                AutomationRule::TRIGGER_PAYMENT_COMPLETED,
                $order->organization_id,
                ['order' => $order, 'payment' => $payment]
            );

            // 5. Security Audit Log
            AuditLog::log(
                'payment.verified',
                $payment,
                null,
                ['amount' => $payment->amount, 'provider' => $payment->provider, 'order' => $order->order_number],
                $payment->organization_id,
                $payment->user_id
            );
        }

        // 6. Handle Digital Wallet Deposit
        $isWalletDeposit = ($payment->request_payload['type'] ?? '') === 'wallet_deposit'
            || ($order && $order->purchase_type === 'wallet_deposit');

        if ($isWalletDeposit && $payment->user_id) {
            $user = User::find($payment->user_id);
            if ($user) {
                $walletService = new \App\Services\Wallet\WalletService();
                $usdAmount = \App\Services\SmmPricing\SmmPricingEngine::convert(
                    (float)$payment->amount,
                    $payment->currency,
                    'USD'
                );
                $walletService->deposit(
                    $user,
                    $usdAmount,
                    $payment->transaction_reference,
                    $payment,
                    "Wallet deposit via {$payment->provider} ({$payment->currency} " . number_format($payment->amount, 2) . ")",
                    ['provider' => $payment->provider, 'original_amount' => $payment->amount, 'currency' => $payment->currency]
                );
            }
        }
    }

    /**
     * Initiate a digital wallet deposit transaction
     */
    public function initiateWalletDeposit(User $user, float $amount, string $currency, string $providerName, array $options = []): array
    {
        $provider = $this->getProvider($providerName);
        $reference = 'DEP-' . strtoupper(Str::random(6)) . '-' . time();

        $payment = Payment::create([
            'organization_id' => $user->organization_id,
            'order_id' => null,
            'user_id' => $user->id,
            'provider' => $providerName,
            'amount' => $amount,
            'currency' => $currency,
            'transaction_reference' => $reference,
            'status' => Payment::STATUS_PENDING,
            'request_payload' => array_merge($options, [
                'type' => 'wallet_deposit',
                'user_id' => $user->id,
            ]),
        ]);

        $result = $provider->initiatePayment($payment, $options);

        if ($result['success'] && !empty($result['provider_reference'])) {
            $payment->update([
                'provider_reference' => $result['provider_reference'],
                'response_payload' => $result['raw'] ?? null,
            ]);
        }

        // Auto-verify for cash/sandbox provider
        if ($providerName === 'cash' || $providerName === 'sandbox') {
            $this->finalizeSuccessfulPayment($payment);
        }

        return [
            'payment' => $payment,
            'redirect_url' => $result['redirect_url'] ?? null,
            'success' => $result['success'],
        ];
    }
}
