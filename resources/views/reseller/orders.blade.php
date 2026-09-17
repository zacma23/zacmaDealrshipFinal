@extends('layouts.admin')

@section('title', 'Reseller Orders')

@section('content')
<div class="space-y-6">
    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Orders & Fulfillment Log</h2>
            <p class="text-xs text-slate-500 mt-0.5">Comprehensive audit trail of all direct reseller campaigns and client orders placed through your child panel.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('customer.smm.new-order') }}" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-cart-plus"></i> Place Order
            </a>
            <a href="{{ route('customer.smm.bulk-orders') }}" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg text-xs shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-layer-group"></i> Mass Order
            </a>
        </div>
    </div>

    <!-- Status Filters Strip -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0">
            @php
                $statuses = [
                    null => 'All Statuses',
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'partial' => 'Partial',
                    'canceled' => 'Canceled',
                    'failed' => 'Failed',
                ];
            @endphp
            @foreach($statuses as $key => $label)
                <a href="{{ route('reseller.orders', ['status' => $key, 'q' => request('q')]) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ request('status') === $key ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('reseller.orders') }}" class="w-full md:w-72 flex items-center gap-2">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="relative w-full">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Order #, link, handle..." class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white">
            </div>
            <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs border border-slate-200">
                Search
            </button>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Order #</th>
                        <th class="py-3 px-4">Service & Target</th>
                        <th class="py-3 px-4">Client</th>
                        <th class="py-3 px-4">Quantity</th>
                        <th class="py-3 px-4">Cost</th>
                        <th class="py-3 px-4">Retail</th>
                        <th class="py-3 px-4">Margin</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Placed At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        @php
                            $profit = max(0, (float)$order->charge - (float)$order->cost);
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
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-600">#{{ $order->order_number }}</td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="font-bold text-slate-800 line-clamp-1">{{ $order->service->name ?? 'Custom Service' }}</div>
                                <div class="text-[11px] text-blue-600 hover:underline font-mono truncate max-w-xs mt-0.5">
                                    <a href="{{ $order->target }}" target="_blank" rel="noopener noreferrer">{{ $order->target }}</a>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-700">{{ $order->user->name ?? 'Direct Agency' }}</span>
                                <div class="text-[10px] text-slate-400">{{ $order->user->email ?? '' }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold text-slate-700">
                                {{ number_format($order->quantity) }}
                                @if($order->remains > 0 && $order->remains < $order->quantity)
                                    <span class="text-[10px] text-amber-600 block">Remains: {{ number_format($order->remains) }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-600">${{ number_format($order->cost, 2) }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">${{ number_format($order->charge, 2) }}</td>
                            <td class="py-3.5 px-4 font-bold text-purple-700">+${{ number_format($profit, 2) }}</td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }} capitalize">
                                    {{ str_replace('_', ' ', $order->status) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-400 text-[11px]">
                                {{ $order->created_at->format('M d, H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-receipt text-3xl mb-2 text-slate-300"></i>
                                <p>No orders found matching your search criteria.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
