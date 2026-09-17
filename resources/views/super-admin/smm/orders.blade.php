@extends('layouts.admin')

@section('title', 'Global SMM Orders Audit')

@section('content')
<div class="space-y-6" x-data="{ refundModal: false, activeOrder: null }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Global SMM Orders Audit</h2>
            <p class="text-xs text-slate-500 mt-0.5">Real-time fulfillment tracking across all tenants, white-label panels, and external suppliers.</p>
        </div>
    </div>

    <!-- Filters Strip -->
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
                <a href="{{ route('super-admin.smm.orders', ['status' => $key, 'q' => request('q')]) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ request('status') === $key ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('super-admin.smm.orders') }}" class="w-full md:w-72 flex items-center gap-2">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="relative w-full">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Order #, target handle/URL..." class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white">
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
                        <th class="py-3 px-4">User & Tenant</th>
                        <th class="py-3 px-4">Gateway</th>
                        <th class="py-3 px-4">Qty / Remains</th>
                        <th class="py-3 px-4">Charge / Cost</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
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
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-700">#{{ $order->order_number }}</td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="font-bold text-slate-800 line-clamp-1">{{ $order->service->name ?? 'Custom Service' }}</div>
                                <div class="text-[11px] text-blue-600 font-mono truncate max-w-xs mt-0.5">
                                    <a href="{{ $order->target }}" target="_blank" rel="noopener noreferrer">{{ $order->target }}</a>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-800">{{ $order->user->name ?? 'System' }}</div>
                                <div class="text-[10px] text-slate-400 font-medium">Tenant: {{ $order->organization->name ?? 'Zacma Core' }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                <div class="text-slate-800 font-semibold">{{ $order->provider->name ?? 'Direct' }}</div>
                                @if($order->provider_order_id)
                                    <div class="text-[10px] text-slate-400">Remote #{{ $order->provider_order_id }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                <span class="font-bold text-slate-800">{{ number_format($order->quantity) }}</span>
                                <span class="text-[10px] text-slate-400 block">Remains: {{ number_format($order->remains) }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">${{ number_format($order->charge, 2) }}</div>
                                <div class="text-[10px] text-slate-400">${{ number_format($order->cost, 2) }} cost</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }} capitalize">
                                    {{ str_replace('_', ' ', $order->status) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    @if(in_array($order->status, ['pending', 'processing', 'in_progress']))
                                        <a href="{{ route('super-admin.smm.orders.sync', $order) }}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-semibold transition" title="Sync Status from Gateway">
                                            <i class="fa-solid fa-arrows-rotate text-blue-600"></i> Sync
                                        </a>
                                        <button @click="activeOrder = { id: {{ $order->id }}, number: '{{ $order->order_number }}', charge: '{{ number_format($order->charge, 2) }}' }; refundModal = true" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded text-xs font-semibold transition" title="Refund Order to User Wallet">
                                            <i class="fa-solid fa-rotate-left"></i> Refund
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                No orders logged.
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

    <!-- Refund Modal -->
    <div x-show="refundModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
        <div @click.away="refundModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 relative">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-bold text-slate-900 text-base">Refund Order</h3>
                <button @click="refundModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form :action="activeOrder ? '/super-admin/smm/orders/' + activeOrder.id + '/refund' : '#'" method="POST" class="space-y-4">
                @csrf
                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900">
                    Canceling order <strong x-text="activeOrder ? '#' + activeOrder.number : ''"></strong> will immediately credit <strong x-text="activeOrder ? '$' + activeOrder.charge : ''"></strong> back into the user's wallet with ledger entry.
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Reason for Refund</label>
                    <input type="text" name="reason" required value="Fulfillment issue / manual admin refund" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="refundModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg text-xs">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-xs shadow-md">Confirm Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
