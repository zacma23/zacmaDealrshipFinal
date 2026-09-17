<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SmmCategory;
use App\Models\SmmOrder;
use App\Models\SmmPlatform;
use App\Models\SmmService;
use App\Models\SmmTicket;
use App\Models\SmmTicketMessage;
use App\Models\WalletTransaction;
use App\Services\Payment\PaymentService;
use App\Services\SmmOrder\SmmOrderEngine;
use App\Services\SmmPricing\SmmPricingEngine;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SmmCustomerPortalController extends Controller
{
    protected SmmOrderEngine $orderEngine;
    protected PaymentService $paymentService;
    protected SmmPricingEngine $pricingEngine;

    public function __construct(
        SmmOrderEngine $orderEngine,
        PaymentService $paymentService,
        SmmPricingEngine $pricingEngine
    ) {
        $this->orderEngine = $orderEngine;
        $this->paymentService = $paymentService;
        $this->pricingEngine = $pricingEngine;
    }

    public function dashboard()
    {
        $user = Auth::user();
        $wallet = $user->getOrCreateWallet();

        $stats = [
            'balance' => (float)$wallet->balance,
            'total_orders' => $user->smmOrders()->count(),
            'completed_orders' => $user->smmOrders()->where('status', SmmOrder::STATUS_COMPLETED)->count(),
            'pending_orders' => $user->smmOrders()->whereIn('status', [SmmOrder::STATUS_PENDING, SmmOrder::STATUS_PROCESSING, SmmOrder::STATUS_IN_PROGRESS])->count(),
            'total_spent' => (float)$wallet->total_spent,
        ];

        $recentOrders = $user->smmOrders()->with('service.platform')->latest()->take(8)->get();
        $recentTransactions = $wallet->transactions()->latest()->take(5)->get();

        return view('customer.smm.dashboard', compact('stats', 'recentOrders', 'recentTransactions', 'wallet'));
    }

    public function newOrder(Request $request)
    {
        $user = Auth::user();
        $wallet = $user->getOrCreateWallet();
        $tenant = app()->has('current_organization') ? app('current_organization') : null;

        $platforms = SmmPlatform::where('is_active', true)
            ->with(['categories' => function ($q) {
                $q->where('is_active', true)->with(['services' => function ($sq) {
                    $sq->where('status', 'active')->orderBy('sort_order');
                }]);
            }])
            ->orderBy('sort_order')
            ->get();

        $selectedServiceId = $request->query('service_id');
        $preselectedService = $selectedServiceId ? SmmService::find($selectedServiceId) : null;

        return view('customer.smm.new-order', compact('platforms', 'wallet', 'preselectedService'));
    }

    public function calculatePrice(Request $request)
    {
        $serviceId = $request->input('service_id');
        $quantity = (int)$request->input('quantity', 1000);
        $service = SmmService::find($serviceId);

        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        $tenant = app()->has('current_organization') ? app('current_organization') : null;
        $pricing = $this->pricingEngine->calculateOrderPricing($service, $quantity, Auth::user(), $tenant);

        return response()->json([
            'rate_per_k' => $pricing['rate_per_k_usd'],
            'total_charge' => $pricing['total_charge_usd'],
            'min_quantity' => $service->min_quantity,
            'max_quantity' => $service->max_quantity,
            'description' => $service->description,
            'start_time' => $service->start_time,
            'completion_time' => $service->completion_time,
            'quality_level' => $service->quality_level,
            'has_refill' => $service->has_refill,
            'dripfeed_supported' => $service->dripfeed_supported,
        ]);
    }

    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'smm_service_id' => 'required|exists:smm_services,id',
            'target' => 'required|string|max:500',
            'quantity' => 'required|integer|min:1',
            'drip_feed' => 'nullable|boolean',
            'runs' => 'nullable|integer|min:2|max:100',
            'interval' => 'nullable|integer|min:10|max:1440',
        ]);

        $service = SmmService::findOrFail($validated['smm_service_id']);
        $user = Auth::user();
        $tenant = app()->has('current_organization') ? app('current_organization') : null;

        try {
            $order = $this->orderEngine->placeOrder(
                $user,
                $service,
                $validated['target'],
                (int)$validated['quantity'],
                [
                    'drip_feed' => $request->boolean('drip_feed'),
                    'runs' => $validated['runs'] ?? null,
                    'interval' => $validated['interval'] ?? null,
                ],
                $tenant
            );

            return redirect()->route('customer.smm.orders.index')
                ->with('success', "Order #{$order->order_number} submitted successfully! Your campaign is now processing.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function services(Request $request)
    {
        $tenant = app()->has('current_organization') ? app('current_organization') : null;
        $user = Auth::user();

        $query = SmmService::with(['platform', 'category', 'provider'])->where('status', 'active');

        if ($request->filled('platform')) {
            $query->whereHas('platform', function ($q) use ($request) {
                $q->where('slug', $request->platform);
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('description', 'like', $term);
            });
        }

        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('customer_price_per_k', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('customer_price_per_k', 'desc');
                    break;
                case 'min_qty':
                    $query->orderBy('min_quantity', 'asc');
                    break;
                default:
                    $query->orderBy('sort_order');
                    break;
            }
        } else {
            $query->orderBy('sort_order');
        }

        $services = $query->paginate(20)->withQueryString();
        $platforms = SmmPlatform::where('is_active', true)->orderBy('sort_order')->get();

        return view('customer.smm.services', compact('services', 'platforms'));
    }

    public function orders(Request $request)
    {
        $user = Auth::user();
        $query = $user->smmOrders()->with(['service.platform', 'service.category']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', $term)
                  ->orWhere('target', 'like', $term)
                  ->orWhereHas('service', fn($sq) => $sq->where('name', 'like', $term));
            });
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        return view('customer.smm.orders', compact('orders'));
    }

    public function refill(SmmOrder $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $result = $this->orderEngine->requestRefill($order);
            if ($result['success']) {
                return back()->with('success', $result['message']);
            }
            return back()->with('error', $result['message']);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(SmmOrder $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $result = $this->orderEngine->requestCancel($order);
            return back()->with('success', $result['message']);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function wallet()
    {
        $user = Auth::user();
        $wallet = $user->getOrCreateWallet();
        $transactions = $wallet->transactions()->latest()->paginate(20);
        $providers = $this->paymentService->getAvailableProviders();

        return view('customer.smm.wallet', compact('wallet', 'transactions', 'providers'));
    }

    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
            'currency' => 'required|string|in:USD,ETB,EUR,GBP',
            'provider' => 'required|string',
        ]);

        $user = Auth::user();

        try {
            $result = $this->paymentService->initiateWalletDeposit(
                $user,
                (float)$validated['amount'],
                $validated['currency'],
                $validated['provider'],
                ['user_email' => $user->email, 'user_phone' => $user->phone]
            );

            if (!empty($result['redirect_url'])) {
                return redirect($result['redirect_url']);
            }

            return redirect()->route('customer.smm.wallet')
                ->with('success', "Deposit of {$validated['currency']} " . number_format($validated['amount'], 2) . " processed successfully to your wallet!");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function bulkOrders()
    {
        $user = Auth::user();
        $wallet = $user->getOrCreateWallet();
        return view('customer.smm.bulk-orders', compact('wallet'));
    }

    public function processBulkOrders(Request $request)
    {
        $validated = $request->validate([
            'bulk_content' => 'required|string',
        ]);

        $lines = preg_split("/\r\n|\n|\r/", trim($validated['bulk_content']));
        $user = Auth::user();
        $tenant = app()->has('current_organization') ? app('current_organization') : null;

        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Expected format: service_id | target_url | quantity
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 3) {
                $failed++;
                $errors[] = "Line " . ($index + 1) . ": Invalid format. Expected 'service_id | link | quantity'";
                continue;
            }

            $serviceId = (int)$parts[0];
            $target = $parts[1];
            $quantity = (int)$parts[2];

            $service = SmmService::find($serviceId);
            if (!$service) {
                $failed++;
                $errors[] = "Line " . ($index + 1) . ": Service #{$serviceId} not found.";
                continue;
            }

            try {
                $this->orderEngine->placeOrder($user, $service, $target, $quantity, [], $tenant);
                $successful++;
            } catch (Exception $e) {
                $failed++;
                $errors[] = "Line " . ($index + 1) . " (Service #{$serviceId}): " . $e->getMessage();
            }
        }

        $msg = "Bulk processing finished: {$successful} order(s) placed successfully, {$failed} failed.";
        return redirect()->route('customer.smm.orders.index')->with('success', $msg)->with('bulk_errors', $errors);
    }

    public function tickets()
    {
        $user = Auth::user();
        $tickets = $user->smmTickets()->with('messages')->latest()->paginate(15);
        $recentOrders = $user->smmOrders()->latest()->take(20)->get();

        return view('customer.smm.tickets', compact('tickets', 'recentOrders'));
    }

    public function storeTicket(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|string|in:order,payment,service,bug,other',
            'smm_order_id' => 'nullable|exists:smm_orders,id',
            'message' => 'required|string|max:3000',
        ]);

        $user = Auth::user();

        $ticket = DB::transaction(function () use ($user, $validated) {
            $ticketNumber = 'TIC-' . rand(10000, 99999);
            $ticket = SmmTicket::create([
                'ticket_number' => $ticketNumber,
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'smm_order_id' => $validated['smm_order_id'] ?? null,
                'subject' => $validated['subject'],
                'category' => $validated['category'],
                'priority' => 'normal',
                'status' => SmmTicket::STATUS_OPEN,
                'last_reply_at' => now(),
            ]);

            SmmTicketMessage::create([
                'smm_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $validated['message'],
                'is_staff' => false,
            ]);

            return $ticket;
        });

        return back()->with('success', "Ticket #{$ticket->ticket_number} created successfully. Our team will respond shortly.");
    }

    public function replyTicket(Request $request, SmmTicket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:3000',
        ]);

        SmmTicketMessage::create([
            'smm_ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'is_staff' => false,
        ]);

        $ticket->update([
            'status' => SmmTicket::STATUS_OPEN,
            'last_reply_at' => now(),
        ]);

        return back()->with('success', 'Your reply has been sent.');
    }
}
