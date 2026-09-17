<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChapaPaymentGateway implements PaymentGatewayInterface
{
    protected string $secretKey;
    protected string $publicKey;
    protected string $webhookSecret;
    protected string $baseUrl = 'https://api.chapa.co/v1';

    public function __construct()
    {
        $this->secretKey = config('services.chapa.secret_key', env('CHAPA_SECRET_KEY', 'CHASECK_TEST-mock-secret-key'));
        $this->publicKey = config('services.chapa.public_key', env('CHAPA_PUBLIC_KEY', 'CHAPUBK_TEST-mock-public-key'));
        $this->webhookSecret = config('services.chapa.webhook_secret', env('CHAPA_WEBHOOK_SECRET', 'mock-webhook-secret'));
    }

    public function initializePayment(Payment $payment, array $customerData, string $returnUrl, string $callbackUrl): array
    {
        $txRef = $payment->transaction_reference;
        $amount = number_format((float) $payment->amount, 2, '.', '');

        // In test/mock environment or when testing flag is on
        if (app()->environment('testing') || str_contains($this->secretKey, 'mock')) {
            $checkoutUrl = url('/checkout/mock-chapa/' . $txRef);
            return [
                'status' => 'success',
                'message' => 'Hosted Link',
                'checkout_url' => $checkoutUrl,
                'data' => [
                    'checkout_url' => $checkoutUrl,
                    'tx_ref' => $txRef,
                ],
            ];
        }

        try {
            $response = Http::withToken($this->secretKey)
                ->timeout(15)
                ->post("{$this->baseUrl}/transaction/initialize", [
                    'amount' => $amount,
                    'currency' => $payment->currency ?: 'ETB',
                    'email' => $customerData['email'] ?? $payment->user?->email,
                    'first_name' => $customerData['first_name'] ?? ($payment->user?->name ?: 'Customer'),
                    'last_name' => $customerData['last_name'] ?? 'User',
                    'phone_number' => $customerData['phone'] ?? $payment->user?->phone,
                    'tx_ref' => $txRef,
                    'callback_url' => $callbackUrl,
                    'return_url' => $returnUrl,
                    'customization' => [
                        'title' => 'Zacma SaaS Subscription',
                        'description' => 'Listing Quota Upgrade',
                    ],
                ]);

            if ($response->successful()) {
                $json = $response->json();
                return [
                    'status' => 'success',
                    'message' => $json['message'] ?? 'Hosted Link',
                    'checkout_url' => $json['data']['checkout_url'] ?? null,
                    'data' => $json['data'] ?? [],
                ];
            }

            Log::error('Chapa initialization failed', ['response' => $response->body()]);
            return [
                'status' => 'error',
                'message' => $response->json('message') ?? 'Failed to initialize payment with Chapa',
                'checkout_url' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('Chapa API exception', ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'checkout_url' => null,
            ];
        }
    }

    public function verifyPayment(string $reference): array
    {
        if (app()->environment('testing') || str_contains($this->secretKey, 'mock')) {
            return [
                'status' => 'success',
                'message' => 'Payment verified successfully',
                'data' => [
                    'status' => 'success',
                    'tx_ref' => $reference,
                    'amount' => 499.00,
                    'currency' => 'ETB',
                ],
            ];
        }

        try {
            $response = Http::withToken($this->secretKey)
                ->timeout(15)
                ->get("{$this->baseUrl}/transaction/verify/{$reference}");

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'status' => 'error',
                'message' => $response->json('message') ?? 'Verification failed',
            ];
        } catch (\Throwable $e) {
            Log::error('Chapa verification exception', ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        // 1. Signature header check
        $signature = $request->header('x-chapa-signature')
            ?? $request->header('chapa-signature')
            ?? $request->header('signature');

        if (!$signature) {
            // If secret is set, header is required
            if (!empty($this->webhookSecret) && !app()->environment('testing')) {
                return false;
            }
            return true;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        if (hash_equals($expectedSignature, $signature)) {
            return true;
        }

        // Also check if payload matched raw secret key
        $expectedWithSecretKey = hash_hmac('sha256', $payload, $this->secretKey);
        return hash_equals($expectedWithSecretKey, $signature);
    }
}

