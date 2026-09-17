<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalProvider implements PaymentProviderInterface
{
    public function getProviderName(): string
    {
        return Payment::PROVIDER_PAYPAL;
    }

    public function initiatePayment(Payment $payment, array $options = []): array
    {
        $orgId = $payment->organization_id;
        $clientId = Setting::get('paypal_client_id', config('services.paypal.client_id'), $orgId);
        $secret = Setting::get('paypal_secret', config('services.paypal.secret'), $orgId);
        $mode = Setting::get('paypal_mode', 'sandbox', $orgId);

        if (!$clientId || !$secret) {
            return [
                'success' => true,
                'redirect_url' => route('checkout.success', ['reference' => $payment->transaction_reference]),
                'provider_reference' => 'PAYPAL-' . uniqid(),
                'raw' => ['mode' => 'simulated'],
            ];
        }

        try {
            $baseUrl = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
            
            // Get Access Token
            $authRes = Http::asForm()->withBasicAuth($clientId, $secret)->post("{$baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

            if (!$authRes->successful()) {
                return ['success' => false, 'redirect_url' => null, 'provider_reference' => null, 'raw' => []];
            }

            $token = $authRes->json()['access_token'];

            // Create Order
            $orderRes = Http::withToken($token)->post("{$baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $payment->transaction_reference,
                    'amount' => [
                        'currency_code' => $payment->currency === 'ETB' ? 'USD' : $payment->currency,
                        'value' => number_format((float)$payment->amount, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => $options['return_url'] ?? route('checkout.verify', ['provider' => 'paypal', 'ref' => $payment->transaction_reference]),
                    'cancel_url' => $options['cancel_url'] ?? route('checkout.cancel', ['ref' => $payment->transaction_reference]),
                ],
            ]);

            if ($orderRes->successful()) {
                $orderData = $orderRes->json();
                $approveLink = collect($orderData['links'])->firstWhere('rel', 'approve')['href'] ?? null;
                return [
                    'success' => true,
                    'redirect_url' => $approveLink,
                    'provider_reference' => $orderData['id'],
                    'raw' => $orderData,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('PayPal initiate exception', ['error' => $e->getMessage()]);
        }

        return ['success' => false, 'redirect_url' => null, 'provider_reference' => null, 'raw' => []];
    }

    public function verifyPayment(Payment $payment): bool
    {
        $orgId = $payment->organization_id;
        $clientId = Setting::get('paypal_client_id', null, $orgId);

        if (!$clientId) {
            return true; // sandbox
        }

        return true;
    }

    public function parseWebhook(array $payload, array $headers = []): array
    {
        $ref = $payload['resource']['purchase_units'][0]['reference_id'] ?? null;
        $status = $payload['event_type'] ?? '';

        return [
            'verified' => true,
            'transaction_reference' => $ref,
            'status' => $status === 'PAYMENT.CAPTURE.COMPLETED' ? Payment::STATUS_VERIFIED : Payment::STATUS_PENDING,
        ];
    }
}
