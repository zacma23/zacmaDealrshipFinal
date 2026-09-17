<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResellerApiKey;
use App\Models\SmmOrder;
use App\Models\SmmService;
use App\Services\SmmOrder\SmmOrderEngine;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SmmApiController extends Controller
{
    protected SmmOrderEngine $orderEngine;

    public function __construct(SmmOrderEngine $orderEngine)
    {
        $this->orderEngine = $orderEngine;
    }

    public function handle(Request $request)
    {
        // 1. Authenticate API Key
        $rawKey = $request->input('key');
        if (!$rawKey && $request->bearerToken()) {
            $rawKey = $request->bearerToken();
        }

        if (empty($rawKey)) {
            return response()->json(['error' => 'API key is required']);
        }

        $apiKey = ResellerApiKey::findByPlainTextKey($rawKey);
        if (!$apiKey) {
            return response()->json(['error' => 'Invalid or inactive API key']);
        }

        $user = $apiKey->user;
        if (!$user || !$user->is_active) {
            return response()->json(['error' => 'Account is suspended or inactive'], 403);
        }

        Auth::setUser($user);

        // IP Whitelist verification if configured
        if (!empty($apiKey->ip_whitelist)) {
            $clientIp = $request->ip();
            if (!in_array($clientIp, $apiKey->ip_whitelist, true)) {
                return response()->json(['error' => "IP [{$clientIp}] is not authorized for this API key"], 403);
            }
        }

        // Rate Limiting
        $rateLimitKey = 'smm_api_rate_' . $apiKey->id;
        $requests = (int)Cache::get($rateLimitKey, 0);
        if ($requests >= $apiKey->rate_limit_per_minute) {
            return response()->json(['error' => 'Rate limit exceeded. Please try again later.'], 429);
        }
        Cache::put($rateLimitKey, $requests + 1, now()->addMinute());

        $apiKey->update(['last_used_at' => now()]);

        // 2. Dispatch Action
        $action = strtolower((string)$request->input('action'));

        return match ($action) {
            'services' => $this->actionServices($user, $apiKey),
            'add' => $this->actionAdd($request, $user, $apiKey),
            'status' => $this->actionStatus($request, $user, $apiKey),
            'balance' => $this->actionBalance($user),
            'refill' => $this->actionRefill($request, $user),
            'cancel' => $this->actionCancel($request, $user),
            default => response()->json(['error' => "Invalid action parameter '{$action}'"], 400),
        };
    }

    protected function actionServices($user, $apiKey)
    {
        $tenant = $apiKey->organization;
        $services = SmmService::with(['platform', 'category'])
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        $output = [];
        foreach ($services as $s) {
            $rate = $s->getEffectiveRate($user, $tenant);
            $output[] = [
                'service' => (int)$s->id,
                'name' => $s->name,
                'type' => ucfirst($s->service_type),
                'category' => ($s->platform ? $s->platform->name . ' - ' : '') . ($s->category ? $s->category->name : 'General'),
                'rate' => (string)number_format($rate, 4, '.', ''),
                'min' => (int)$s->min_quantity,
                'max' => (int)$s->max_quantity,
                'refill' => (bool)$s->has_refill,
                'cancel' => (bool)$s->has_cancel,
                'dripfeed' => (bool)$s->dripfeed_supported,
            ];
        }

        return response()->json($output);
    }

    protected function actionAdd(Request $request, $user, $apiKey)
    {
        $serviceId = (int)$request->input('service');
        $target = (string)$request->input('link');
        $quantity = (int)$request->input('quantity');

        if (!$serviceId || empty($target) || !$quantity) {
            return response()->json(['error' => 'Parameters [service, link, quantity] are required'], 400);
        }

        $service = SmmService::find($serviceId);
        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        $runs = $request->filled('runs') ? (int)$request->input('runs') : null;
        $interval = $request->filled('interval') ? (int)$request->input('interval') : null;

        try {
            $order = $this->orderEngine->placeOrder(
                $user,
                $service,
                $target,
                $quantity,
                [
                    'drip_feed' => ($runs !== null && $interval !== null),
                    'runs' => $runs,
                    'interval' => $interval,
                ],
                $apiKey->organization,
                true,
                $apiKey->id
            );

            return response()->json(['order' => (int)$order->id]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    protected function actionStatus(Request $request, $user, $apiKey)
    {
        // Batch status request: orders=1,2,3
        if ($request->filled('orders')) {
            $orderIds = array_filter(array_map('intval', explode(',', $request->input('orders'))));
            $orders = SmmOrder::where('user_id', $user->id)->whereIn('id', $orderIds)->get();

            $results = [];
            foreach ($orderIds as $id) {
                $order = $orders->firstWhere('id', $id);
                if (!$order) {
                    $results[$id] = ['error' => 'Incorrect order ID'];
                } else {
                    $results[$id] = [
                        'charge' => (string)number_format($order->charge, 2, '.', ''),
                        'start_count' => (string)($order->start_count ?? 0),
                        'status' => ucfirst(str_replace('_', ' ', $order->status)),
                        'remains' => (string)($order->remains ?? 0),
                        'currency' => $order->currency,
                    ];
                }
            }

            return response()->json($results);
        }

        // Single status request: order=12345
        $orderId = (int)$request->input('order');
        if (!$orderId) {
            return response()->json(['error' => 'Order parameter is required'], 400);
        }

        $order = SmmOrder::where('user_id', $user->id)->where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json([
            'charge' => (string)number_format($order->charge, 2, '.', ''),
            'start_count' => (string)($order->start_count ?? 0),
            'status' => ucfirst(str_replace('_', ' ', $order->status)),
            'remains' => (string)($order->remains ?? 0),
            'currency' => $order->currency,
        ]);
    }

    protected function actionBalance($user)
    {
        $wallet = $user->getOrCreateWallet();

        return response()->json([
            'balance' => (string)number_format($wallet->balance, 2, '.', ''),
            'currency' => $wallet->currency,
        ]);
    }

    protected function actionRefill(Request $request, $user)
    {
        $orderId = (int)$request->input('order');
        $order = SmmOrder::where('user_id', $user->id)->where('id', $orderId)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        try {
            $result = $this->orderEngine->requestRefill($order);
            if ($result['success']) {
                return response()->json(['refill' => (string)rand(10000, 99999)]);
            }
            return response()->json(['error' => $result['message']], 400);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    protected function actionCancel(Request $request, $user)
    {
        $orderId = (int)$request->input('order');
        $order = SmmOrder::where('user_id', $user->id)->where('id', $orderId)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        try {
            $result = $this->orderEngine->requestCancel($order);
            return response()->json(['cancel' => (string)$order->id]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
