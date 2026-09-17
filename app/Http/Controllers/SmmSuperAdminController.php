<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\SmmCategory;
use App\Models\SmmOrder;
use App\Models\SmmPlatform;
use App\Models\SmmProvider;
use App\Models\SmmService;
use App\Models\SmmTicket;
use App\Models\SmmTicketMessage;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\SmmOrder\SmmOrderEngine;
use App\Services\SmmProvider\SmmProviderManager;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SmmSuperAdminController extends Controller
{
    protected SmmOrderEngine $orderEngine;
    protected SmmProviderManager $providerManager;
    protected WalletService $walletService;

    public function __construct(
        SmmOrderEngine $orderEngine,
        SmmProviderManager $providerManager,
        WalletService $walletService
    ) {
        $this->orderEngine = $orderEngine;
        $this->providerManager = $providerManager;
        $this->walletService = $walletService;
    }

    public function dashboard()
    {
        $totalRevenue = (float)SmmOrder::sum('charge');
        $totalCost = (float)SmmOrder::sum('cost');
        $grossProfit = max(0, $totalRevenue - $totalCost);

        $stats = [
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'gross_profit' => $grossProfit,
            'total_orders' => SmmOrder::count(),
            'pending_orders' => SmmOrder::whereIn('status', [SmmOrder::STATUS_PENDING, SmmOrder::STATUS_PROCESSING, SmmOrder::STATUS_IN_PROGRESS])->count(),
            'completed_orders' => SmmOrder::where('status', SmmOrder::STATUS_COMPLETED)->count(),
            'failed_orders' => SmmOrder::where('status', SmmOrder::STATUS_FAILED)->count(),
            'active_providers' => SmmProvider::where('status', 'active')->count(),
            'active_child_panels' => Organization::where('custom_domain_status', 'active')->count(),
            'total_wallet_balance' => (float)Wallet::sum('balance'),
        ];

        $recentOrders = SmmOrder::with(['user', 'service.platform', 'provider'])->latest()->take(10)->get();
        $providers = SmmProvider::latest()->get();

        return view('super-admin.smm.dashboard', compact('stats', 'recentOrders', 'providers'));
    }

    public function platforms()
    {
        $platforms = SmmPlatform::with('categories')->orderBy('sort_order')->get();
        return view('super-admin.smm.platforms', compact('platforms'));
    }

    public function storePlatform(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:smm_platforms,slug',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
        ]);

        SmmPlatform::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'icon' => $validated['icon'] ?? 'fa-solid fa-layer-group',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'New platform created successfully.');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'smm_platform_id' => 'required|exists:smm_platforms,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
        ]);

        SmmCategory::create([
            'smm_platform_id' => $validated['smm_platform_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'icon' => $validated['icon'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'New category created successfully.');
    }

    public function services(Request $request)
    {
        $query = SmmService::with(['platform', 'category', 'provider']);

        if ($request->filled('platform')) {
            $query->whereHas('platform', fn($q) => $q->where('slug', $request->platform));
        }

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(fn($q) => $q->where('name', 'like', $term)->orWhere('provider_service_id', 'like', $term));
        }

        $services = $query->orderBy('sort_order')->paginate(20)->withQueryString();
        $platforms = SmmPlatform::where('is_active', true)->with('categories')->get();
        $providers = SmmProvider::where('status', 'active')->get();

        return view('super-admin.smm.services', compact('services', 'platforms', 'providers'));
    }

    public function storeService(Request $request)
    {
        $validated = $request->validate([
            'smm_platform_id' => 'required|exists:smm_platforms,id',
            'smm_category_id' => 'required|exists:smm_categories,id',
            'smm_provider_id' => 'nullable|exists:smm_providers,id',
            'provider_service_id' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'cost_per_k' => 'required|numeric|min:0',
            'customer_price_per_k' => 'required|numeric|min:0',
            'reseller_price_per_k' => 'required|numeric|min:0',
            'min_quantity' => 'required|integer|min:1',
            'max_quantity' => 'required|integer|gt:min_quantity',
            'quality_level' => 'nullable|string|max:100',
            'start_time' => 'nullable|string|max:100',
            'completion_time' => 'nullable|string|max:100',
            'has_refill' => 'nullable|boolean',
            'has_cancel' => 'nullable|boolean',
            'dripfeed_supported' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        SmmService::create([
            'smm_platform_id' => $validated['smm_platform_id'],
            'smm_category_id' => $validated['smm_category_id'],
            'smm_provider_id' => $validated['smm_provider_id'] ?? null,
            'provider_service_id' => $validated['provider_service_id'] ?? null,
            'name' => $validated['name'],
            'cost_per_k' => (float)$validated['cost_per_k'],
            'customer_price_per_k' => (float)$validated['customer_price_per_k'],
            'reseller_price_per_k' => (float)$validated['reseller_price_per_k'],
            'min_quantity' => (int)$validated['min_quantity'],
            'max_quantity' => (int)$validated['max_quantity'],
            'quality_level' => $validated['quality_level'] ?? 'High Quality',
            'start_time' => $validated['start_time'] ?? 'Instant',
            'completion_time' => $validated['completion_time'] ?? '24 Hours',
            'has_refill' => $request->boolean('has_refill'),
            'refill_days' => $request->boolean('has_refill') ? 30 : 0,
            'has_cancel' => $request->boolean('has_cancel'),
            'dripfeed_supported' => $request->boolean('dripfeed_supported'),
            'description' => $validated['description'] ?? '',
            'status' => 'active',
        ]);

        return back()->with('success', 'Service created successfully.');
    }

    public function providers()
    {
        $providers = SmmProvider::withCount(['services', 'orders'])->get();
        return view('super-admin.smm.providers', compact('providers'));
    }

    public function storeProvider(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_url' => 'required|url',
            'api_key' => 'required|string|max:500',
            'adapter_type' => 'required|string|in:standard_v2,mock_sandbox',
        ]);

        SmmProvider::create([
            'name' => $validated['name'],
            'api_url' => $validated['api_url'],
            'api_key' => $validated['api_key'],
            'adapter_type' => $validated['adapter_type'],
            'status' => 'active',
            'health_status' => 'healthy',
        ]);

        return back()->with('success', 'New SMM provider configured.');
    }

    public function testProvider(SmmProvider $provider)
    {
        $adapter = $this->providerManager->getAdapter($provider);
        $result = $adapter->getBalance();

        if ($result['success']) {
            return back()->with('success', "Connection successful! Provider balance: {$result['balance']} {$result['currency']}");
        }

        return back()->with('error', "Connection failed: " . ($result['error'] ?? 'Unknown error'));
    }

    public function orders(Request $request)
    {
        $query = SmmOrder::with(['user', 'service.platform', 'provider', 'organization']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(fn($q) => $q->where('order_number', 'like', $term)->orWhere('target', 'like', $term));
        }

        $orders = $query->latest()->paginate(20)->withQueryString();
        return view('super-admin.smm.orders', compact('orders'));
    }

    public function syncOrder(SmmOrder $order)
    {
        $this->orderEngine->syncOrderStatus($order);
        return back()->with('success', "Order #{$order->order_number} status synchronized.");
    }

    public function refundOrder(Request $request, SmmOrder $order)
    {
        $reason = $request->input('reason', 'Refunded by Super Admin');
        $this->orderEngine->failAndRefundOrder($order, $reason);
        return back()->with('success', "Order #{$order->order_number} refunded to user wallet.");
    }

    public function wallets()
    {
        $wallets = Wallet::with('user.organization')->latest()->paginate(20);
        $totalBalance = (float)Wallet::sum('balance');
        $totalDeposited = (float)Wallet::sum('total_deposited');
        $totalSpent = (float)Wallet::sum('total_spent');

        return view('super-admin.smm.wallets', compact('wallets', 'totalBalance', 'totalDeposited', 'totalSpent'));
    }

    public function adjustWallet(Request $request, Wallet $wallet)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'notes' => 'required|string|max:255',
        ]);

        $amount = (float)$validated['amount'];
        $user = $wallet->user;

        if ($amount > 0) {
            $this->walletService->manualCredit($user, $amount, $validated['notes'], Auth::user());
            return back()->with('success', "Credited $" . number_format($amount, 2) . " to {$user->name}.");
        } elseif ($amount < 0) {
            $this->walletService->manualDebit($user, abs($amount), $validated['notes'], Auth::user());
            return back()->with('success', "Debited $" . number_format(abs($amount), 2) . " from {$user->name}.");
        }

        return back()->with('error', 'Amount cannot be zero.');
    }

    public function childPanels()
    {
        $childPanels = Organization::withCount('users')->latest()->paginate(15);
        return view('super-admin.smm.child-panels', compact('childPanels'));
    }

    public function tickets()
    {
        $tickets = SmmTicket::with(['user', 'assignedStaff', 'messages'])->latest()->paginate(20);
        return view('super-admin.smm.tickets', compact('tickets'));
    }

    public function replyTicket(Request $request, SmmTicket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:3000',
            'status' => 'nullable|string|in:open,in_progress,answered,closed',
            'is_internal_note' => 'nullable|boolean',
        ]);

        SmmTicketMessage::create([
            'smm_ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'is_staff' => true,
            'is_internal_note' => $request->boolean('is_internal_note'),
        ]);

        $ticket->update([
            'status' => $validated['status'] ?? SmmTicket::STATUS_ANSWERED,
            'assigned_user_id' => Auth::id(),
            'last_reply_at' => now(),
        ]);

        return back()->with('success', 'Staff response recorded.');
    }
}
