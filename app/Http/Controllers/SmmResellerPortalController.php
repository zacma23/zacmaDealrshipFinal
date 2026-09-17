<?php

namespace App\Http\Controllers;

use App\Models\BulkCustomerImport;
use App\Models\Organization;
use App\Models\ResellerApiKey;
use App\Models\SmmBulkOrder;
use App\Models\SmmOrder;
use App\Models\SmmPlatform;
use App\Models\SmmService;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Payment\PaymentService;
use App\Services\SmmOrder\SmmOrderEngine;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SmmResellerPortalController extends Controller
{
    protected SmmOrderEngine $orderEngine;
    protected WalletService $walletService;
    protected PaymentService $paymentService;

    public function __construct(
        SmmOrderEngine $orderEngine,
        WalletService $walletService,
        PaymentService $paymentService
    ) {
        $this->orderEngine = $orderEngine;
        $this->walletService = $walletService;
        $this->paymentService = $paymentService;
    }

    public function dashboard()
    {
        $user = Auth::user();
        $orgId = $user->organization_id;
        $wallet = $user->getOrCreateWallet();

        $orgOrders = SmmOrder::where('organization_id', $orgId)->orWhere('user_id', $user->id);

        $totalSales = (float)$orgOrders->sum('charge');
        $totalCost = (float)$orgOrders->sum('cost');
        $netProfit = max(0, $totalSales - $totalCost);

        $stats = [
            'balance' => (float)$wallet->balance,
            'total_sales' => $totalSales,
            'net_profit' => $netProfit,
            'total_orders' => $orgOrders->count(),
            'pending_orders' => (clone $orgOrders)->whereIn('status', [SmmOrder::STATUS_PENDING, SmmOrder::STATUS_PROCESSING, SmmOrder::STATUS_IN_PROGRESS])->count(),
            'completed_orders' => (clone $orgOrders)->where('status', SmmOrder::STATUS_COMPLETED)->count(),
            'total_customers' => User::where('organization_id', $orgId)->where('role', User::ROLE_CUSTOMER)->count(),
        ];

        $recentOrders = (clone $orgOrders)->with(['service', 'user'])->latest()->take(8)->get();
        $topServices = SmmService::withCount(['orders' => fn($q) => $q->where('organization_id', $orgId)])
            ->orderBy('orders_count', 'desc')
            ->take(5)
            ->get();

        return view('reseller.dashboard', compact('stats', 'recentOrders', 'topServices', 'wallet'));
    }

    public function services(Request $request)
    {
        $user = Auth::user();
        $query = SmmService::with(['platform', 'category', 'provider'])->where('status', 'active');

        if ($request->filled('platform')) {
            $query->whereHas('platform', fn($q) => $q->where('slug', $request->platform));
        }

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(fn($q) => $q->where('name', 'like', $term)->orWhere('description', 'like', $term));
        }

        $services = $query->orderBy('sort_order')->paginate(20)->withQueryString();
        $platforms = SmmPlatform::where('is_active', true)->orderBy('sort_order')->get();

        return view('reseller.services', compact('services', 'platforms'));
    }

    public function customers()
    {
        $user = Auth::user();
        $orgId = $user->organization_id;

        $customers = User::where('organization_id', $orgId)
            ->where('role', User::ROLE_CUSTOMER)
            ->with('wallet')
            ->withCount('smmOrders')
            ->latest()
            ->paginate(15);

        return view('reseller.customers', compact('customers'));
    }

    public function storeCustomer(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:25',
            'initial_balance' => 'nullable|numeric|min:0',
        ]);

        $randomPassword = Str::random(12);

        $customer = User::create([
            'organization_id' => $user->organization_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($randomPassword),
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $initialBalance = (float)($validated['initial_balance'] ?? 0);
        if ($initialBalance > 0) {
            $this->walletService->deposit(
                $customer,
                $initialBalance,
                'TXN-INIT-' . strtoupper(Str::random(6)),
                null,
                "Initial balance allocated by reseller"
            );
        }

        return back()->with('success', "Customer account created! Temporary password: {$randomPassword}");
    }

    public function toggleCustomer(User $customer)
    {
        if ($customer->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }

        $customer->update(['is_active' => !$customer->is_active]);
        $status = $customer->is_active ? 'activated' : 'suspended';
        return back()->with('success', "Customer {$customer->name} has been {$status}.");
    }

    public function adjustCustomerBalance(Request $request, User $customer)
    {
        if ($customer->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric',
            'notes' => 'required|string|max:255',
        ]);

        $amount = (float)$validated['amount'];
        if ($amount > 0) {
            $this->walletService->manualCredit($customer, $amount, $validated['notes'], Auth::user());
            return back()->with('success', "Successfully credited $" . number_format($amount, 2) . " to customer wallet.");
        } elseif ($amount < 0) {
            $this->walletService->manualDebit($customer, abs($amount), $validated['notes'], Auth::user());
            return back()->with('success', "Successfully debited $" . number_format(abs($amount), 2) . " from customer wallet.");
        }

        return back()->with('error', 'Amount cannot be zero.');
    }

    public function bulkCustomerImport(Request $request)
    {
        $validated = $request->validate([
            'csv_content' => 'required|string',
        ]);

        $lines = preg_split("/\r\n|\n|\r/", trim($validated['csv_content']));
        $user = Auth::user();

        $imported = 0;
        $duplicates = 0;
        $invalid = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Name, Email, Phone, InitialBalance
            $parts = array_map('trim', str_getcsv($line));
            if (count($parts) < 2) {
                $invalid++;
                continue;
            }

            $name = $parts[0];
            $email = $parts[1];
            $phone = $parts[2] ?? null;
            $initialBal = isset($parts[3]) ? (float)$parts[3] : 0.0;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid++;
                continue;
            }

            if (User::where('email', $email)->exists()) {
                $duplicates++;
                continue;
            }

            $newUser = User::create([
                'organization_id' => $user->organization_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make(Str::random(12)),
                'role' => User::ROLE_CUSTOMER,
                'is_active' => true,
            ]);

            if ($initialBal > 0) {
                $this->walletService->deposit($newUser, $initialBal, 'TXN-BULK-' . strtoupper(Str::random(6)), null, 'Bulk imported balance');
            }

            $imported++;
        }

        BulkCustomerImport::create([
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
            'file_name' => 'manual_csv_import_' . date('Ymd_His'),
            'total_rows' => count($lines),
            'valid_rows' => $imported,
            'invalid_rows' => $invalid,
            'duplicate_rows' => $duplicates,
            'imported_count' => $imported,
            'status' => 'completed',
        ]);

        return back()->with('success', "Import complete: {$imported} customers imported, {$duplicates} duplicates skipped, {$invalid} invalid rows.");
    }

    public function orders(Request $request)
    {
        $user = Auth::user();
        $orgId = $user->organization_id;

        $query = SmmOrder::where('organization_id', $orgId)->with(['service', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(fn($q) => $q->where('order_number', 'like', $term)->orWhere('target', 'like', $term));
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        return view('reseller.orders', compact('orders'));
    }

    public function apiDocs()
    {
        $user = Auth::user();
        $apiKeys = $user ? $user->resellerApiKeys()->latest()->get() : collect();
        return view('reseller.api-docs', compact('apiKeys'));
    }

    public function generateApiKey(Request $request)
    {
        $user = Auth::user();
        $name = $request->input('name', 'API Key ' . date('Y-m-d'));
        $result = ResellerApiKey::generateForUser($user, $name);

        return back()->with('success', "New API Key generated! Please copy it now, it will not be displayed again: " . $result['plainTextKey']);
    }

    public function revokeApiKey(ResellerApiKey $apiKey)
    {
        if ($apiKey->user_id !== Auth::id()) {
            abort(403);
        }

        $apiKey->delete();
        return back()->with('success', 'API Key revoked successfully.');
    }

    public function childPanel()
    {
        $user = Auth::user();
        $org = $user->organization;

        return view('reseller.child-panel', compact('org'));
    }

    public function updateChildPanel(Request $request)
    {
        $user = Auth::user();
        $org = $user->organization;

        if (!$org) {
            return back()->with('error', 'You must belong to an active organization to configure a child panel.');
        }

        $validated = $request->validate([
            'brand_name' => 'required|string|max:255',
            'custom_domain' => 'nullable|string|max:255',
            'subdomain' => 'nullable|string|max:100',
            'default_markup' => 'required|numeric|min:0|max:500',
            'markup_type' => 'required|string|in:percentage,fixed',
            'primary_color' => 'nullable|string|max:20',
            'support_email' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:50',
            'telegram' => 'nullable|string|max:50',
            'allow_public_registration' => 'nullable|boolean',
        ]);

        $theme = $org->theme_config ?? [];
        $theme['primary_color'] = $validated['primary_color'] ?? '#2563EB';

        $contacts = $org->contact_details ?? [];
        $contacts['email'] = $validated['support_email'] ?? null;
        $contacts['whatsapp'] = $validated['whatsapp'] ?? null;
        $contacts['telegram'] = $validated['telegram'] ?? null;

        $org->update([
            'brand_name' => $validated['brand_name'],
            'custom_domain' => $validated['custom_domain'] ?? $org->custom_domain,
            'subdomain' => $validated['subdomain'] ?? $org->subdomain,
            'default_markup' => (float)$validated['default_markup'],
            'markup_type' => $validated['markup_type'],
            'theme_config' => $theme,
            'contact_details' => $contacts,
            'allow_public_registration' => $request->boolean('allow_public_registration'),
            'custom_domain_status' => !empty($validated['custom_domain']) ? 'active' : 'inactive',
        ]);

        return back()->with('success', 'Child Panel white-label branding and pricing updated successfully!');
    }
}
