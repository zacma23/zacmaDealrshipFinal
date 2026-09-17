@extends('layouts.admin')

@section('title', 'SMM Command Center')

@section('content')
<div class="space-y-6">
    <!-- Header & Quick Jump -->
    <div class="bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-950 rounded-2xl p-6 text-white shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 text-xs font-semibold mb-2">
                <i class="fa-solid fa-server"></i> Global Platform Operations
            </div>
            <h2 class="text-2xl font-black tracking-tight">SMM Marketplace Command Center</h2>
            <p class="text-slate-300 text-xs sm:text-sm mt-1 max-w-2xl leading-relaxed">
                Super Admin control over wholesale API gateways, provider failovers, multi-tenant child panels, and liquidity ledgers.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('super-admin.smm.services') }}" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-lg text-xs shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-layer-group"></i> Manage Catalog
            </a>
            <a href="{{ route('super-admin.smm.providers') }}" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-lg text-xs border border-slate-700 transition flex items-center gap-1.5">
                <i class="fa-solid fa-network-wired"></i> Upstream Gateways
            </a>
        </div>
    </div>

    <!-- Financial & Operational Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Total Revenue -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total SMM Revenue</span>
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-dollar-sign"></i></span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900">${{ number_format($stats['total_revenue'], 2) }}</div>
                <div class="text-xs text-slate-500 mt-1">Gross platform billings</div>
            </div>
        </div>

        <!-- Provider Cost -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Provider Fulfillment Cost</span>
                <span class="p-2 bg-rose-50 text-rose-600 rounded-lg text-sm"><i class="fa-solid fa-arrow-trend-down"></i></span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900">${{ number_format($stats['total_cost'], 2) }}</div>
                <div class="text-xs text-slate-500 mt-1">Wholesale API spend</div>
            </div>
        </div>

        <!-- Gross Platform Profit -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Gross Platform Margin</span>
                <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg text-sm"><i class="fa-solid fa-chart-line"></i></span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-emerald-600">${{ number_format($stats['gross_profit'], 2) }}</div>
                <div class="text-xs text-emerald-700 mt-1 font-semibold">Net profit realized</div>
            </div>
        </div>

        <!-- User Wallet Liquidity -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total User Liquidity</span>
                <span class="p-2 bg-purple-50 text-purple-600 rounded-lg text-sm"><i class="fa-solid fa-vault"></i></span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900">${{ number_format($stats['total_wallet_balance'], 2) }}</div>
                <div class="text-xs text-slate-500 mt-1">Prepaid wallet balances</div>
            </div>
        </div>
    </div>

    <!-- Secondary Stats: Orders & System Health -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-base">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-slate-500">Total Orders</div>
                <div class="text-lg font-black text-slate-800">{{ number_format($stats['total_orders']) }}</div>
            </div>
        </div>

        <div class="bg-amber-50/60 p-4 rounded-xl border border-amber-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-base">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-amber-700">Active / Pending</div>
                <div class="text-lg font-black text-amber-900">{{ number_format($stats['pending_orders']) }}</div>
            </div>
        </div>

        <div class="bg-emerald-50/60 p-4 rounded-xl border border-emerald-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-base">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-emerald-700">Completed</div>
                <div class="text-lg font-black text-emerald-900">{{ number_format($stats['completed_orders']) }}</div>
            </div>
        </div>

        <div class="bg-indigo-50/60 p-4 rounded-xl border border-indigo-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-base">
                <i class="fa-solid fa-network-wired"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-indigo-700">Active Gateways</div>
                <div class="text-lg font-black text-indigo-900">{{ number_format($stats['active_providers']) }} Upstream</div>
            </div>
        </div>
    </div>

    <!-- Live Orders Stream & Provider Status -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Live Orders Feed (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Global Live Orders Stream</h3>
                    <p class="text-xs text-slate-400">All client & reseller transactions across the platform</p>
                </div>
                <a href="{{ route('super-admin.smm.orders') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Full Audit Log &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                            <th class="py-3 px-4">Order #</th>
                            <th class="py-3 px-4">Service</th>
                            <th class="py-3 px-4">Customer</th>
                            <th class="py-3 px-4">Gateway</th>
                            <th class="py-3 px-4">Charge / Cost</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentOrders as $order)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-700">#{{ $order->order_number }}</td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-800 line-clamp-1">{{ $order->service->name ?? 'Custom Service' }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono truncate max-w-[180px]">{{ $order->target }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-slate-800 font-medium">{{ $order->user->name ?? 'System' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $order->user->role ?? '' }}</div>
                                </td>
                                <td class="py-3 px-4 text-slate-600 font-mono">
                                    {{ $order->provider->name ?? 'Direct' }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-900">${{ number_format($order->charge, 2) }}</span>
                                    <span class="text-[10px] text-slate-400 block">${{ number_format($order->cost, 2) }} cost</span>
                                </td>
                                <td class="py-3 px-4">
                                    @php
                                        $badges = [
                                            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'in_progress' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'processing' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'partial' => 'bg-purple-50 text-purple-700 border-purple-200',
                                            'canceled' => 'bg-slate-100 text-slate-600 border-slate-200',
                                            'failed' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        ];
                                        $badgeClass = $badges[$order->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }} capitalize">
                                        {{ str_replace('_', ' ', $order->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400">
                                    No orders logged yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Provider Gateways & Controls (1 Col) -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800 text-sm">Active Upstream Gateways</h3>
                    <a href="{{ route('super-admin.smm.providers') }}" class="text-xs text-blue-600 font-semibold hover:underline">Manage &rarr;</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse($providers as $provider)
                        <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                            <div class="flex items-center justify-between">
                                <div class="font-bold text-xs text-slate-800">{{ $provider->name }}</div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $provider->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($provider->status) }}
                                </span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-[11px] text-slate-500 font-mono">
                                <span>Balance: ${{ number_format($provider->balance, 2) }}</span>
                                <span class="uppercase text-[10px] bg-slate-200 px-1.5 py-0.5 rounded">{{ $provider->adapter_type }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">No providers configured.</p>
                    @endforelse
                </div>
            </div>

            <!-- Child Panels Overview -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800 text-sm">White-Label Tenancy</h3>
                    <a href="{{ route('super-admin.smm.child-panels') }}" class="text-xs text-blue-600 font-semibold hover:underline">View Panels &rarr;</a>
                </div>
                <div class="mt-3 text-xs text-slate-600 leading-relaxed">
                    <p><strong>{{ $stats['active_child_panels'] }}</strong> child panel domains are active and routing wholesale traffic.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
