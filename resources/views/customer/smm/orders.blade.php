@extends('layouts.app')

@section('title', 'My SMM Orders')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold text-blue-600 mb-1">
                <a href="{{ route('customer.smm.dashboard') }}" class="hover:underline">Dashboard</a> &bull;
                <span>Order History</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">My Campaigns & Orders</h1>
            <p class="text-sm text-slate-500">Track real-time delivery, request refills, and monitor campaign performance.</p>
        </div>
        <a href="{{ route('customer.smm.new-order') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-xl shadow transition flex items-center space-x-2">
            <i class="fa-solid fa-plus"></i>
            <span>New Order</span>
        </a>
    </div>

    <!-- Status Filter Pills -->
    <div class="flex flex-wrap gap-2 text-xs font-semibold">
        @php
            $currentStatus = request('status');
            $statuses = [
                '' => 'All Orders',
                'pending' => 'Pending',
                'processing' => 'Processing',
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                'partial' => 'Partial',
                'cancelled' => 'Cancelled',
                'refunded' => 'Refunded',
            ];
        @endphp
        @foreach($statuses as $k => $label)
            <a href="{{ request()->fullUrlWithQuery(['status' => $k ?: null]) }}"
               class="px-3.5 py-1.5 rounded-xl border transition {{ $currentStatus === $k || (!$currentStatus && $k === '') ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Search Form -->
    <form action="{{ url()->current() }}" method="GET" class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-xs"></i>
            <input type="text"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Search by Order ID (#ZAC-...), Target URL, or Service name..."
                   class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
        <button type="submit" class="bg-slate-900 hover:bg-black text-white text-xs font-bold px-4 py-2 rounded-xl transition shadow">
            Search
        </button>
    </form>

    <!-- Orders Table -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5">Order ID</th>
                        <th class="px-5 py-3.5">Service</th>
                        <th class="px-5 py-3.5">Target</th>
                        <th class="px-5 py-3.5">Quantity</th>
                        <th class="px-5 py-3.5">Charge</th>
                        <th class="px-5 py-3.5">Start / Remains</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Date</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-4 font-mono font-bold text-slate-900">
                                #{{ $order->order_number }}
                                @if($order->is_drip_feed)
                                    <span class="block text-[10px] text-blue-600 font-sans font-bold">Drip-Feed ({{ $order->drip_runs }}x)</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 max-w-[220px]">
                                <div class="font-bold text-slate-900 truncate" title="{{ $order->service->name ?? '' }}">
                                    {{ $order->service->name ?? 'Custom Service' }}
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $order->service->platform->name ?? '' }} &bull; {{ $order->service->category->name ?? '' }}
                                </div>
                            </td>
                            <td class="px-5 py-4 max-w-[180px] truncate text-slate-600 font-mono text-[11px]">
                                <a href="{{ $order->target }}" target="_blank" class="hover:text-blue-600 hover:underline flex items-center gap-1">
                                    <span class="truncate">{{ $order->target }}</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                </a>
                            </td>
                            <td class="px-5 py-4 font-bold text-slate-800">{{ number_format($order->quantity) }}</td>
                            <td class="px-5 py-4 font-black text-slate-900 text-sm">
                                ${{ number_format($order->charge, 2) }}
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <div class="font-medium text-[11px]">Start: {{ $order->start_count !== null ? number_format($order->start_count) : '-' }}</div>
                                <div class="text-[11px] text-slate-400">Remains: {{ $order->remains !== null ? number_format($order->remains) : '-' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $badge = match($order->status) {
                                        'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        'in_progress', 'processing' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                                        'partial' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        'cancelled', 'failed', 'refunded' => 'bg-rose-100 text-rose-800 border-rose-200',
                                        default => 'bg-slate-100 text-slate-800 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border {{ $badge }}">
                                    {{ str_replace('_', ' ', $order->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-500 text-[11px] whitespace-nowrap">
                                {{ $order->created_at->format('M d, Y') }}
                                <span class="block text-[10px] text-slate-400">{{ $order->created_at->format('H:i') }}</span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-1 whitespace-nowrap">
                                @if($order->canRefill())
                                    <form action="{{ route('customer.smm.orders.refill', $order) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-[10px] rounded-lg border border-emerald-200 transition"
                                                title="Request Free Refill">
                                            Refill
                                        </button>
                                    </form>
                                @endif

                                @if($order->canCancel())
                                    <form action="{{ route('customer.smm.orders.cancel', $order) }}" method="POST" class="inline" onsubmit="return confirm('Cancel this order and refund balance?')">
                                        @csrf
                                        <button type="submit"
                                                class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-[10px] rounded-lg border border-rose-200 transition"
                                                title="Cancel & Refund">
                                            Cancel
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center text-slate-400">
                                <i class="fa-solid fa-box-open text-3xl text-slate-300 mb-2"></i>
                                <p class="text-sm">No orders found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
