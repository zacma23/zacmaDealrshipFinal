<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CardProvider implements PaymentProviderInterface
{
    public function getProviderName(): string
    {
        return Payment::PROVIDER_CARD;
    }

    public function initiatePayment(Payment $payment, array $options = []): array
    {
        $orgId = $payment->organization_id;
        $stripeSecret = Setting::get('stripe_secret_key', config('services.stripe.secret'), $orgId);

        if (!$stripeSecret) {
            return [
                'success' => true,
                'redirect_url' => route('checkout.success', ['reference' => $payment->transaction_reference]),
                'provider_reference' => 'CARD-' . uniqid(),
                'raw' => ['mode' => 'simulated'],
            ];
        }

        try {
            $order = $payment->order;
            $res = Http::withToken($stripeSecret)->asForm()->post('https://api.stripe.com/v1/checkout/sessions', [
                'success_url' => $options['return_url'] ?? route('checkout.verify', ['provider' => 'card', 'ref' => $payment->transaction_reference]),
                'cancel_url' => $options['cancel_url'] ?? route('checkout.cancel', ['ref' => $payment->transaction_reference]),
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'client_reference_id' => $payment->transaction_reference,
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($payment->currency === 'ETB' ? 'USD' : $payment->currency),
                        'unit_amount' => (int) round($payment->amount * 100),
                        'product_data' => [
                            'name' => 'Order #' . ($order ? $order->order_number : $payment->transaction_reference),
                        ],
                    ],
                    'quantity' => 1,
                ]],
            ]);

            if ($res->successful()) {
                $data = $res->json();
                return [
                    'success' => true,
                    'redirect_url' => $data['url'] ?? null,
                    'provider_reference' => $data['id'],
                    'raw' => $data,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('Stripe card exception', ['error' => $e->getMessage()]);
        }

        return ['success' => false, 'redirect_url' => null, 'provider_reference' => null, 'raw' => []];
    }

    public function verifyPayment(Payment $payment): bool
    {
        $orgId = $payment->organization_id;
        $stripeSecret = Setting::get('stripe_secret_key', null, $orgId);

        if (!$stripeSecret) {
            return true; // sandbox
        }

        return true;
    }

    public function parseWebhook(array $payload, array $headers = []): array
    {
        $ref = $payload['data']['object']['client_reference_id'] ?? null;
        $status = $payload['type'] ?? '';

        return [
            'verified' => true,
            'transaction_reference' => $ref,
            'status' => $status === 'checkout.session.completed' ? Payment::STATUS_VERIFIED : Payment::STATUS_PENDING,
        ];
    }
}
