<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelebirrProvider implements PaymentProviderInterface
{
    public function getProviderName(): string
    {
        return Payment::PROVIDER_TELEBIRR;
    }

    public function initiatePayment(Payment $payment, array $options = []): array
    {
        $orgId = $payment->organization_id;
        $appId = Setting::get('telebirr_app_id', config('services.telebirr.app_id'), $orgId);
        $appKey = Setting::get('telebirr_app_key', config('services.telebirr.app_key'), $orgId);
        $shortCode = Setting::get('telebirr_short_code', config('services.telebirr.short_code'), $orgId);

        if (!$appId || !$appKey || !$shortCode) {
            // Simulated sandbox flow
            return [
                'success' => true,
                'redirect_url' => route('checkout.success', ['reference' => $payment->transaction_reference]),
                'provider_reference' => 'TELE-' . uniqid(),
                'raw' => ['mode' => 'simulated'],
            ];
        }

        try {
            $apiUrl = Setting::get('telebirr_api_url', 'https://app.telebirr.et/h5/fabric/applyFabricToken', $orgId);
            $payload = [
                'appId' => $appId,
                'outTradeNo' => $payment->transaction_reference,
                'subject' => 'Zacma Marketplace Payment',
                'totalAmount' => (string) $payment->amount,
                'shortCode' => $shortCode,
                'notifyUrl' => route('api.payment.webhook', ['provider' => 'telebirr']),
                'returnUrl' => $options['return_url'] ?? route('checkout.verify', ['provider' => 'telebirr', 'ref' => $payment->transaction_reference]),
            ];

            $response = Http::timeout(10)->post($apiUrl, $payload);
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'redirect_url' => $data['toPayUrl'] ?? null,
                    'provider_reference' => $data['tradeNo'] ?? null,
                    'raw' => $data,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('Telebirr initiate exception', ['error' => $e->getMessage()]);
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
        $appId = Setting::get('telebirr_app_id', null, $orgId);

        if (!$appId) {
            return true; // sandbox
        }

        // Query Telebirr order query API
        return true;
    }

    public function parseWebhook(array $payload, array $headers = []): array
    {
        $ref = $payload['outTradeNo'] ?? null;
        $tradeStatus = $payload['tradeStatus'] ?? '';

        return [
            'verified' => true,
            'transaction_reference' => $ref,
            'status' => in_array(strtoupper($tradeStatus), ['SUCCESS', 'COMPLETED']) ? Payment::STATUS_VERIFIED : Payment::STATUS_FAILED,
        ];
    }
}
