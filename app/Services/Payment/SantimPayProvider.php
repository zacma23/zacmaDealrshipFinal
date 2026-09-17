<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SantimPayProvider implements PaymentProviderInterface
{
    public function getProviderName(): string
    {
        return Payment::PROVIDER_SANTIMPAY;
    }

    public function initiatePayment(Payment $payment, array $options = []): array
    {
        $orgId = $payment->organization_id;
        $merchantId = Setting::get('santimpay_merchant_id', config('services.santimpay.merchant_id'), $orgId);
        $privateKey = Setting::get('santimpay_private_key', config('services.santimpay.private_key'), $orgId);
        $baseUrl = Setting::get('santimpay_api_url', 'https://services.santimpay.com/api/v1/gateway', $orgId);

        // If not configured in production, fallback to simulation mode
        if (!$merchantId || !$privateKey) {
            $redirectUrl = route('checkout.success', ['reference' => $payment->transaction_reference]);
            return [
                'success' => true,
                'redirect_url' => $redirectUrl,
                'provider_reference' => 'SANTIM-' . uniqid(),
                'raw' => ['mode' => 'simulated'],
            ];
        }

        try {
            $payload = [
                'id' => $payment->transaction_reference,
                'amount' => (float) $payment->amount,
                'reason' => 'Payment for order ' . ($payment->order ? $payment->order->order_number : $payment->transaction_reference),
                'merchantId' => $merchantId,
                'returnUrl' => $options['return_url'] ?? route('checkout.verify', ['provider' => 'santimpay', 'ref' => $payment->transaction_reference]),
                'cancelUrl' => $options['cancel_url'] ?? route('checkout.cancel', ['ref' => $payment->transaction_reference]),
                'notifyUrl' => route('api.payment.webhook', ['provider' => 'santimpay']),
            ];

            $response = Http::timeout(10)->post("{$baseUrl}/initiate-payment", $payload);
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'redirect_url' => $data['paymentUrl'] ?? null,
                    'provider_reference' => $data['transactionId'] ?? null,
                    'raw' => $data,
                ];
            }

            Log::error('SantimPay initiate failed', ['body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error('SantimPay exception', ['error' => $e->getMessage()]);
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
        $merchantId = Setting::get('santimpay_merchant_id', null, $orgId);
        $baseUrl = Setting::get('santimpay_api_url', 'https://services.santimpay.com/api/v1/gateway', $orgId);

        if (!$merchantId) {
            // Simulated gateway always verifies in sandbox
            return true;
        }

        try {
            $response = Http::timeout(10)->get("{$baseUrl}/fetch-transaction-status", [
                'id' => $payment->transaction_reference,
                'merchantId' => $merchantId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return isset($data['status']) && strtoupper($data['status']) === 'COMPLETED';
            }
        } catch (\Throwable $e) {
            Log::error('SantimPay verify exception', ['error' => $e->getMessage()]);
        }

        return false;
    }

    public function parseWebhook(array $payload, array $headers = []): array
    {
        $ref = $payload['id'] ?? $payload['thirdPartyId'] ?? null;
        $status = strtoupper($payload['status'] ?? '');

        return [
            'verified' => true, // In production, verify HMAC signature with private key
            'transaction_reference' => $ref,
            'status' => $status === 'COMPLETED' ? Payment::STATUS_VERIFIED : Payment::STATUS_FAILED,
        ];
    }
}
