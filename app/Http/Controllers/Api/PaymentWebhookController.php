<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    public function handle(string $provider, Request $request): JsonResponse
    {
        Log::info("Received payment webhook for {$provider}", ['payload' => $request->all()]);

        $gateway = $this->paymentService->getGateway($provider);

        // 1. Verify Webhook Signature
        if (!$gateway->verifyWebhookSignature($request)) {
            Log::warning("Invalid webhook signature received for {$provider}");
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid signature.',
            ], 403);
        }

        // 2. Extract transaction reference
        $reference = $request->input('tx_ref')
            ?? $request->input('trx_ref')
            ?? $request->input('transaction_reference')
            ?? $request->input('reference');

        if (!$reference) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction reference is missing.',
            ], 422);
        }

        $idempotencyKey = $request->header('idempotency-key')
            ?? $request->input('idempotency_key')
            ?? ($reference . '-webhook');

        // 3. Process with idempotency
        $result = $this->paymentService->processWebhookPayment(
            $reference,
            $request->all(),
            $idempotencyKey
        );

        if (!($result['success'] ?? false)) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'] ?? 'Payment processing failed.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'idempotent' => $result['idempotent'] ?? false,
        ]);
    }
}

