@extends('layouts.admin')

@section('title', 'Reseller Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome Banner & Quick Links -->
    <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-purple-800 rounded-2xl p-6 text-white shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-xs font-semibold mb-2 text-blue-100">
                <i class="fa-solid fa-crown text-amber-300"></i> Wholesale Agency & Reseller Portal
            </div>
            <h2 class="text-2xl font-black tracking-tight">Welcome back, {{ Auth::user()->name }}!</h2>
            <p class="text-blue-100 text-sm mt-1 max-w-2xl">
                Manage your agency clients, configure your white-label child panel, track automated order margins, and access wholesale rates.
            </p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('customer.smm.new-order') }}" class="px-4 py-2.5 bg-white text-blue-700 hover:bg-blue-50 font-bold rounded-xl text-xs sm:text-sm shadow-md transition flex items-center gap-2">
                <i class="fa-solid fa-plus-circle"></i> Place Reseller Order
            </a>
            <a href="{{ route('reseller.child-panel') }}" class="px-4 py-2.5 bg-blue-900/60 hover:bg-blue-900 text-white font-bold rounded-xl text-xs sm:text-sm border border-white/20 transition flex items-center gap-2">
                <i class="fa-solid fa-globe"></i> White-Label Setup
            </a>
        </div>
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Reseller Wallet -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Wholesale Wallet</span>
                <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg text-sm"><i class="fa-solid fa-wallet"></i></span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900">${{ number_format($stats['balance'], 2) }}</div>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-xs text-slate-500">Reserved: ${{ number_format($wallet->reserved_balance, 2) }}</span>
                    <a href="{{ route('customer.smm.wallet') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Top Up &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Total Retail Sales -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Client Sales</span>
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-chart-line"></i></span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900">${{ number_format($stats['total_sales'], 2) }}</div>
                <p class="text-xs text-slate-500 mt-2">Across all customer orders</p>
            </div>
        </div>

        <!-- Net Profit Margin -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Estimated Net Profit</span>
                <span class="p-2 bg-purple-50 text-purple-600 rounded-lg text-sm"><i class="fa-solid fa-coins"></i></span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-purple-600">${{ number_format($stats['net_profit'], 2) }}</div>
                <p class="text-xs text-slate-500 mt-2">Markup over provider cost</p>
            </div>
        </div>

        <!-- Total Clients -->
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Clients</span>
                <span class="p-2 bg-amber-50 text-amber-600 rounded-lg text-sm"><i class="fa-solid fa-users"></i></span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900">{{ number_format($stats['total_customers']) }}</div>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-xs text-slate-500">Under your agency</span>
                    <a href="{{ route('reseller.customers') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Manage &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Status Summary Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-base">
                <i class="fa-solid fa-box"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-slate-500">Total Orders</div>
                <div class="text-lg font-black text-slate-800">{{ number_format($stats['total_orders']) }}</div>
            </div>
        </div>

        <div class="bg-amber-50/60 p-4 rounded-xl border border-amber-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-base">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-amber-700">In Progress / Active</div>
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
                <i class="fa-solid fa-code"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-indigo-700">API Integration</div>
                <a href="{{ route('reseller.api-docs') }}" class="text-xs font-bold text-indigo-600 hover:underline">View v2 Docs &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Main Content: Recent Orders & Top Services -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Orders (2 Columns) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Recent Reseller & Client Orders</h3>
                    <p class="text-xs text-slate-400">Live order fulfillment stream</p>
                </div>
                <a href="{{ route('reseller.orders') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">View All Orders &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                            <th class="py-3 px-4">Order #</th>
                            <th class="py-3 px-4">Service</th>
                            <th class="py-3 px-4">Client</th>
                            <th class="py-3 px-4">Qty</th>
                            <th class="py-3 px-4">Amount</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentOrders as $order)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4 font-mono font-bold text-slate-700">#{{ $order->order_number }}</td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-800 line-clamp-1">{{ $order->service->name ?? 'Custom Service' }}</div>
                                    <div class="text-[11px] text-slate-400 truncate max-w-[200px]">{{ $order->target }}</div>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ $order->user->name ?? 'Direct' }}
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-700">{{ number_format($order->quantity) }}</td>
                                <td class="py-3 px-4 font-bold text-slate-900">${{ number_format($order->charge, 2) }}</td>
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
                                    <i class="fa-solid fa-inbox text-3xl mb-2 text-slate-300"></i>
                                    <p>No orders recorded yet. Place your first reseller order or connect via API.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Services & Quick Tools (1 Column) -->
        <div class="space-y-6">
            <!-- Top Services Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="font-bold text-slate-800 text-sm">Top Performing Services</h3>
                    <a href="{{ route('reseller.services') }}" class="text-xs text-blue-600 font-semibold hover:underline">Rate List &rarr;</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse($topServices as $service)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">
                                    <i class="{{ $service->platform->icon ?? 'fa-solid fa-share-nodes' }}"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-800 line-clamp-1">{{ $service->name }}</div>
                                    <div class="text-[11px] text-slate-400">Wholesale: ${{ number_format($service->reseller_price_per_k, 2) }}/k</div>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-slate-700">{{ $service->orders_count ?? 0 }} orders</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">No top services data available.</p>
                    @endforelse
                </div>
            </div>

            <!-- White-Label Status Banner -->
            <div class="bg-slate-900 text-white rounded-xl p-5 shadow-sm">
                <div class="flex items-center gap-2 text-amber-400 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> White-Label Child Panel
                </div>
                <h4 class="font-bold text-base mt-2">Sell Under Your Own Domain</h4>
                <p class="text-slate-300 text-xs mt-1 leading-relaxed">
                    Point your custom domain (e.g., panel.youragency.com) to Zacma and give your clients a 100% white-labeled portal with your markups.
                </p>
                <div class="mt-4">
                    <a href="{{ route('reseller.child-panel') }}" class="block text-center py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs transition">
                        Configure Child Panel &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
