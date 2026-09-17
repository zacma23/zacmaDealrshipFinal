<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SantimPayPaymentGateway implements PaymentGatewayInterface
{
    protected string $merchantId;
    protected string $privateKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->merchantId = config('services.santimpay.merchant_id', env('SANTIMPAY_MERCHANT_ID', 'mock-santimpay-merchant-id'));
        $this->privateKey = config('services.santimpay.private_key', env('SANTIMPAY_PRIVATE_KEY', 'mock-santimpay-private-key'));
        $this->baseUrl = config('services.santimpay.base_url', 'https://services.santimpay.com/api/v1/gateway');
    }

    public function initializePayment(Payment $payment, array $customerData, string $returnUrl, string $callbackUrl): array
    {
        $txRef = $payment->transaction_reference;
        $amount = number_format((float) $payment->amount, 2, '.', '');

        if (app()->environment('testing') || str_contains($this->privateKey, 'mock')) {
            $checkoutUrl = url('/checkout/mock-santimpay/' . $txRef);
            return [
                'status' => 'success',
                'message' => 'SantimPay Checkout Link Generated',
                'checkout_url' => $checkoutUrl,
                'data' => [
                    'checkout_url' => $checkoutUrl,
                    'tx_ref' => $txRef,
                    'gateway' => 'santimpay',
                ],
            ];
        }

        try {
            $response = Http::withToken($this->privateKey)
                ->timeout(15)
                ->post("{$this->baseUrl}/initiate-payment", [
                    'id' => $txRef,
                    'amount' => $amount,
                    'reason' => 'Zacma SaaS Subscription',
                    'merchantId' => $this->merchantId,
                    'successRedirectUrl' => $returnUrl,
                    'failureRedirectUrl' => $returnUrl,
                    'notifyUrl' => $callbackUrl,
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
                'message' => $response->json('message') ?? 'Failed to initialize SantimPay payment.',
            ];
        } catch (\Throwable $e) {
            Log::error('SantimPay init exception', ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => 'SantimPay gateway is currently unavailable: ' . $e->getMessage(),
            ];
        }
    }

    public function verifyPayment(string $reference): array
    {
        if (app()->environment('testing') || str_contains($this->privateKey, 'mock')) {
            return [
                'status' => 'success',
                'reference' => $reference,
                'amount' => 300.00,
                'currency' => 'ETB',
            ];
        }

        try {
            $response = Http::withToken($this->privateKey)
                ->timeout(15)
                ->get("{$this->baseUrl}/check-payment-status/{$reference}");

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

        return !empty($request->header('X-Santimpay-Signature'));
    }
}

