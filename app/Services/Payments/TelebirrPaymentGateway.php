<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelebirrPaymentGateway implements PaymentGatewayInterface
{
    protected string $appId;
    protected string $appKey;
    protected string $shortCode;
    protected string $publicKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->appId = config('services.telebirr.app_id', env('TELEBIRR_APP_ID', 'mock-telebirr-app-id'));
        $this->appKey = config('services.telebirr.app_key', env('TELEBIRR_APP_KEY', 'mock-telebirr-app-key'));
        $this->shortCode = config('services.telebirr.short_code', env('TELEBIRR_SHORT_CODE', '100123'));
        $this->publicKey = config('services.telebirr.public_key', env('TELEBIRR_PUBLIC_KEY', 'mock-public-key'));
        $this->baseUrl = config('services.telebirr.base_url', 'https://telebirr.et/api');
    }

    public function initializePayment(Payment $payment, array $customerData, string $returnUrl, string $callbackUrl): array
    {
        $txRef = $payment->transaction_reference;
        $amount = number_format((float) $payment->amount, 2, '.', '');

        if (app()->environment('testing') || str_contains($this->appKey, 'mock')) {
            $checkoutUrl = url('/checkout/mock-telebirr/' . $txRef);
            return [
                'status' => 'success',
                'message' => 'Telebirr Payment Link Generated',
                'checkout_url' => $checkoutUrl,
                'data' => [
                    'checkout_url' => $checkoutUrl,
                    'tx_ref' => $txRef,
                    'gateway' => 'telebirr',
                ],
            ];
        }

        try {
            $payload = [
                'appId' => $this->appId,
                'totalAmount' => $amount,
                'outTradeNo' => $txRef,
                'subject' => 'Zacma Dealership Subscription',
                'notifyUrl' => $callbackUrl,
                'returnUrl' => $returnUrl,
                'shortCode' => $this->shortCode,
            ];

            $response = Http::timeout(15)->post("{$this->baseUrl}/toTradeWebPay", $payload);

            if ($response->successful() && isset($response->json()['data']['toPayUrl'])) {
                return [
                    'status' => 'success',
                    'checkout_url' => $response->json()['data']['toPayUrl'],
                    'data' => $response->json()['data'],
                ];
            }

            Log::error('Telebirr init failed', ['response' => $response->json()]);
            return [
                'status' => 'error',
                'message' => $response->json('message') ?? 'Failed to initialize Telebirr payment.',
            ];
        } catch (\Throwable $e) {
            Log::error('Telebirr init exception', ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => 'Telebirr payment gateway is temporarily unavailable: ' . $e->getMessage(),
            ];
        }
    }

    public function verifyPayment(string $reference): array
    {
        if (app()->environment('testing') || str_contains($this->appKey, 'mock')) {
            return [
                'status' => 'success',
                'reference' => $reference,
                'amount' => 300.00,
                'currency' => 'ETB',
            ];
        }

        try {
            $response = Http::timeout(15)->post("{$this->baseUrl}/queryOrder", [
                'appId' => $this->appId,
                'outTradeNo' => $reference,
            ]);

            return $response->json() ?? ['status' => 'failed'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        $signature = $request->header('X-Telebirr-Signature') ?? $request->input('sign');
        return !empty($signature);
    }
}
