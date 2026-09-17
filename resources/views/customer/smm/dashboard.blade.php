@extends('layouts.app')

@section('title', 'SMM Customer Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Header with Quick Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gradient-to-r from-blue-900 to-indigo-900 rounded-2xl p-6 text-white shadow-xl">
        <div class="space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-blue-300">Customer Command Center</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Welcome back, {{ Auth::user()->name }}</h1>
            <p class="text-sm text-slate-300">Boost your social media presence with instant automated delivery.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('customer.smm.new-order') }}" class="bg-blue-500 hover:bg-blue-600 text-white font-bold text-sm px-5 py-2.5 rounded-xl shadow-lg transition flex items-center space-x-2">
                <i class="fa-solid fa-bolt"></i>
                <span>New Order</span>
            </a>
            <a href="{{ route('customer.smm.wallet') }}" class="bg-white/10 hover:bg-white/20 text-white border border-white/20 font-bold text-sm px-4 py-2.5 rounded-xl shadow transition flex items-center space-x-2">
                <i class="fa-solid fa-wallet"></i>
                <span>Add Funds</span>
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Balance Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Wallet Balance</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">${{ number_format($stats['balance'], 2) }}</h3>
                <a href="{{ route('customer.smm.wallet') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 mt-1 inline-block">Deposit Funds &rarr;</a>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-wallet"></i>
            </div>
        </div>

        <!-- Total Orders Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Orders</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['total_orders']) }}</h3>
                <span class="text-xs text-slate-400">All campaigns</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
        </div>

        <!-- In Progress Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Active Campaigns</p>
                <h3 class="text-2xl font-black text-amber-600 mt-1">{{ number_format($stats['pending_orders']) }}</h3>
                <span class="text-xs text-slate-400">Processing & In Progress</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>

        <!-- Total Spent Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Spent</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">${{ number_format($stats['total_spent'], 2) }}</h3>
                <span class="text-xs text-slate-400">{{ number_format($stats['completed_orders']) }} Completed</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-chart-line"></i>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200 flex space-x-6">
        <a href="{{ route('customer.smm.dashboard') }}" class="pb-3 text-sm font-bold text-blue-600 border-b-2 border-blue-600">Overview</a>
        <a href="{{ route('customer.smm.new-order') }}" class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-900">New Order</a>
        <a href="{{ route('customer.smm.orders.index') }}" class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-900">My Orders</a>
        <a href="{{ route('customer.smm.services') }}" class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-900">Services List</a>
        <a href="{{ route('customer.smm.bulk-orders') }}" class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-900">Bulk Orders</a>
        <a href="{{ route('customer.smm.wallet') }}" class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-900">Wallet & Deposits</a>
        <a href="{{ route('customer.smm.tickets') }}" class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-900">Support</a>
    </div>

    <!-- Recent Orders & Transactions Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Orders (2 cols) -->
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-blue-600"></i>
                    <span>Recent Campaigns</span>
                </h3>
                <a href="{{ route('customer.smm.orders.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">View All &rarr;</a>
            </div>
            @if($recentOrders->isEmpty())
                <div class="p-8 text-center text-slate-500 space-y-3">
                    <i class="fa-solid fa-box-open text-4xl text-slate-300"></i>
                    <p class="text-sm">You haven't placed any SMM orders yet.</p>
                    <a href="{{ route('customer.smm.new-order') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700">Place Your First Order</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase">
                            <tr>
                                <th class="px-5 py-3">Order ID</th>
                                <th class="px-5 py-3">Service</th>
                                <th class="px-5 py-3">Target</th>
                                <th class="px-5 py-3">Quantity</th>
                                <th class="px-5 py-3">Charge</th>
                                <th class="px-5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($recentOrders as $order)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-5 py-3 font-mono font-bold text-slate-700">#{{ $order->order_number }}</td>
                                    <td class="px-5 py-3 font-medium text-slate-900 max-w-[200px] truncate" title="{{ $order->service->name ?? '' }}">
                                        {{ $order->service->name ?? 'Custom Service' }}
                                    </td>
                                    <td class="px-5 py-3 text-slate-500 max-w-[150px] truncate">
                                        <a href="{{ $order->target }}" target="_blank" class="hover:text-blue-600 underline">{{ $order->target }}</a>
                                    </td>
                                    <td class="px-5 py-3 font-semibold text-slate-700">{{ number_format($order->quantity) }}</td>
                                    <td class="px-5 py-3 font-bold text-slate-900">${{ number_format($order->charge, 2) }}</td>
                                    <td class="px-5 py-3">
                                        @php
                                            $badge = match($order->status) {
                                                'completed' => 'bg-emerald-100 text-emerald-800',
                                                'in_progress', 'processing' => 'bg-blue-100 text-blue-800',
                                                'pending' => 'bg-amber-100 text-amber-800',
                                                'partial' => 'bg-purple-100 text-purple-800',
                                                'cancelled', 'failed', 'refunded' => 'bg-rose-100 text-rose-800',
                                                default => 'bg-slate-100 text-slate-800',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $badge }}">
                                            {{ str_replace('_', ' ', $order->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Recent Ledger Transactions (1 col) -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-emerald-600"></i>
                    <span>Wallet Activity</span>
                </h3>
                <a href="{{ route('customer.smm.wallet') }}" class="text-xs font-semibold text-blue-600 hover:underline">Ledger &rarr;</a>
            </div>

            @if($recentTransactions->isEmpty())
                <p class="text-xs text-slate-400 py-4 text-center">No transaction history yet.</p>
            @else
                <div class="divide-y divide-slate-100 space-y-3">
                    @foreach($recentTransactions as $txn)
                        <div class="pt-3 flex items-start justify-between">
                            <div class="space-y-0.5">
                                <div class="text-xs font-semibold text-slate-800">{{ $txn->description }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $txn->reference }} &bull; {{ $txn->created_at->diffForHumans() }}</div>
                            </div>
                            <div class="text-xs font-bold {{ $txn->amount >= 0 ? 'text-emerald-600' : 'text-slate-800' }}">
                                {{ $txn->amount >= 0 ? '+' : '' }}${{ number_format($txn->amount, 2) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="pt-4 border-t border-slate-100">
                <a href="{{ route('customer.smm.wallet') }}" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs py-2 rounded-xl flex items-center justify-center space-x-2 transition">
                    <i class="fa-solid fa-plus-circle"></i>
                    <span>Top-up Wallet Balance</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
