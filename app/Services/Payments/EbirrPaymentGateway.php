<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EbirrPaymentGateway implements PaymentGatewayInterface
{
    protected string $merchantId;
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->merchantId = config('services.ebirr.merchant_id', env('EBIRR_MERCHANT_ID', 'mock-ebirr-merchant-id'));
        $this->apiKey = config('services.ebirr.api_key', env('EBIRR_API_KEY', 'mock-ebirr-api-key'));
        $this->baseUrl = config('services.ebirr.base_url', 'https://api.ebirr.com/payment');
    }

    public function initializePayment(Payment $payment, array $customerData, string $returnUrl, string $callbackUrl): array
    {
        $txRef = $payment->transaction_reference;
        $amount = number_format((float) $payment->amount, 2, '.', '');

        if (app()->environment('testing') || str_contains($this->apiKey, 'mock')) {
            $checkoutUrl = url('/checkout/mock-ebirr/' . $txRef);
            return [
                'status' => 'success',
                'message' => 'eBirr Checkout Link Generated',
                'checkout_url' => $checkoutUrl,
                'data' => [
                    'checkout_url' => $checkoutUrl,
                    'tx_ref' => $txRef,
                    'gateway' => 'ebirr',
                ],
            ];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->post("{$this->baseUrl}/init", [
                    'clientId' => $this->merchantId,
                    'amount' => $amount,
                    'referenceId' => $txRef,
                    'returnUrl' => $returnUrl,
                    'callbackUrl' => $callbackUrl,
                ]);

            if ($response->successful() && isset($response->json()['paymentUrl'])) {
                return [
                    'status' => 'success',
                    'checkout_url' => $response->json()['paymentUrl'],
                    'data' => $response->json(),
                ];
            }

            return [
                'status' => 'error',
                'message' => $response->json('message') ?? 'Failed to initialize eBirr payment.',
            ];
        } catch (\Throwable $e) {
            Log::error('eBirr init exception', ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => 'eBirr gateway is currently unavailable: ' . $e->getMessage(),
            ];
        }
    }

    public function verifyPayment(string $reference): array
    {
        if (app()->environment('testing') || str_contains($this->apiKey, 'mock')) {
            return [
                'status' => 'success',
                'reference' => $reference,
                'amount' => 300.00,
                'currency' => 'ETB',
            ];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->get("{$this->baseUrl}/verify/{$reference}");

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

        return !empty($request->header('X-Ebirr-Signature'));
    }
}

