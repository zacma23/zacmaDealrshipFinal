<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChapaProvider implements PaymentProviderInterface
{
    public function getProviderName(): string
    {
        return Payment::PROVIDER_CHAPA;
    }

    public function initiatePayment(Payment $payment, array $options = []): array
    {
        $orgId = $payment->organization_id;
        $secretKey = Setting::get('chapa_secret_key', config('services.chapa.secret_key'), $orgId);

        if (!$secretKey) {
            return [
                'success' => true,
                'redirect_url' => route('checkout.success', ['reference' => $payment->transaction_reference]),
                'provider_reference' => 'CHAPA-' . uniqid(),
                'raw' => ['mode' => 'simulated'],
            ];
        }

        try {
            $user = $payment->user;
            $order = $payment->order;
            
            $payload = [
                'amount' => (string) $payment->amount,
                'currency' => $payment->currency,
                'email' => $order ? $order->customer_email : ($user ? $user->email : 'customer@zacma.com'),
                'first_name' => $order ? $order->customer_name : ($user ? $user->name : 'Customer'),
                'tx_ref' => $payment->transaction_reference,
                'callback_url' => route('api.payment.webhook', ['provider' => 'chapa']),
                'return_url' => $options['return_url'] ?? route('checkout.verify', ['provider' => 'chapa', 'ref' => $payment->transaction_reference]),
                'customization' => [
                    'title' => 'Zacma Marketplace Checkout',
                    'description' => 'Payment for order #' . ($order ? $order->order_number : $payment->transaction_reference),
                ],
            ];

            $response = Http::withToken($secretKey)
                ->timeout(10)
                ->post('https://api.chapa.co/v1/transaction/initialize', $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'success' => true,
                        'redirect_url' => $data['data']['checkout_url'] ?? null,
                        'provider_reference' => $data['data']['checkout_url'] ?? null,
                        'raw' => $data,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::error('Chapa initiate exception', ['error' => $e->getMessage()]);
        }

        return [
            'success' => false,
            'redirect_url' => null,
            'provider_reference' => null,
            'raw' => [],
        ];
    }

    public function verifyPayment(Payment $payment): bool
    {
        $orgId = $payment->organization_id;
        $secretKey = Setting::get('chapa_secret_key', null, $orgId);

        if (!$secretKey) {
            return true;
        }

        try {
            $response = Http::withToken($secretKey)
                ->timeout(10)
                ->get("https://api.chapa.co/v1/transaction/verify/{$payment->transaction_reference}");

            if ($response->successful()) {
                $data = $response->json();
                return isset($data['status']) && $data['status'] === 'success'
                    && isset($data['data']['status']) && $data['data']['status'] === 'success';
            }
        } catch (\Throwable $e) {
            Log::error('Chapa verify exception', ['error' => $e->getMessage()]);
        }

        return false;
    }

    public function parseWebhook(array $payload, array $headers = []): array
    {
        $ref = $payload['tx_ref'] ?? null;
        $status = $payload['status'] ?? '';

        return [
            'verified' => true,
            'transaction_reference' => $ref,
            'status' => $status === 'success' ? Payment::STATUS_VERIFIED : Payment::STATUS_FAILED,
        ];
    }
}
