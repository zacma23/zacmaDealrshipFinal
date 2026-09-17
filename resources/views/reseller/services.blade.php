@extends('layouts.admin')

@section('title', 'Wholesale SMM Services & Rates')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Wholesale Rate Card & Catalog</h2>
            <p class="text-xs text-slate-500 mt-0.5">Special agency rates per 1,000 units with calculated retail profit margins.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('customer.smm.new-order') }}" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-cart-plus"></i> New Order
            </a>
            <a href="{{ route('reseller.api-docs') }}" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg text-xs shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-code"></i> API Access
            </a>
        </div>
    </div>

    <!-- Filters Strip -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Platform Pills -->
        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0">
            <a href="{{ route('reseller.services') }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ !request('platform') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                All Platforms
            </a>
            @foreach($platforms as $p)
                <a href="{{ route('reseller.services', ['platform' => $p->slug, 'q' => request('q')]) }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap flex items-center gap-1.5 {{ request('platform') === $p->slug ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <i class="{{ $p->icon }}"></i>
                    <span>{{ $p->name }}</span>
                </a>
            @endforeach
        </div>

        <!-- Search Input -->
        <form method="GET" action="{{ route('reseller.services') }}" class="w-full md:w-72 flex items-center gap-2">
            @if(request('platform'))
                <input type="hidden" name="platform" value="{{ request('platform') }}">
            @endif
            <div class="relative w-full">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search wholesale services..." class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white">
            </div>
            <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs border border-slate-200">
                Filter
            </button>
        </form>
    </div>

    <!-- Wholesale Services Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">ID</th>
                        <th class="py-3 px-4">Platform & Service</th>
                        <th class="py-3 px-4">Min / Max</th>
                        <th class="py-3 px-4">Wholesale Rate</th>
                        <th class="py-3 px-4">Retail Rate</th>
                        <th class="py-3 px-4">Est. Margin</th>
                        <th class="py-3 px-4">Features</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($services as $service)
                        @php
                            $margin = max(0, $service->customer_price_per_k - $service->reseller_price_per_k);
                            $marginPercent = $service->reseller_price_per_k > 0 ? round(($margin / $service->reseller_price_per_k) * 100) : 0;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-500">#{{ $service->id }}</td>
                            <td class="py-3.5 px-4 max-w-sm">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                        <i class="{{ $service->platform->icon ?? 'fa-solid fa-share-nodes' }}"></i>
                                        {{ $service->platform->name ?? 'Universal' }}
                                    </span>
                                    <span class="text-[11px] text-slate-400 font-medium">{{ $service->category->name ?? '' }}</span>
                                </div>
                                <div class="font-bold text-slate-800 text-sm line-clamp-1">{{ $service->name }}</div>
                                @if($service->description)
                                    <div class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">{{ $service->description }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-mono text-slate-700 font-medium">{{ number_format($service->min_quantity) }} - {{ number_format($service->max_quantity) }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="text-emerald-700 font-extrabold text-sm">${{ number_format($service->reseller_price_per_k, 2) }}</span>
                                <span class="text-[10px] text-slate-400">/ 1k</span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 font-medium">
                                ${{ number_format($service->customer_price_per_k, 2) }} / 1k
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="text-purple-700 font-bold">+${{ number_format($margin, 2) }}</div>
                                <div class="text-[10px] text-purple-500 font-semibold">{{ $marginPercent }}% markup</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1">
                                    @if($service->has_refill)
                                        <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[10px] font-semibold" title="30-Day Guaranteed Refill">
                                            <i class="fa-solid fa-rotate-right"></i> Refill
                                        </span>
                                    @endif
                                    @if($service->has_cancel)
                                        <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded text-[10px] font-semibold" title="Cancel Supported">
                                            <i class="fa-solid fa-xmark"></i> Cancel
                                        </span>
                                    @endif
                                    @if($service->dripfeed_supported)
                                        <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded text-[10px] font-semibold" title="Drip-feed Enabled">
                                            <i class="fa-solid fa-faucet-drip"></i> Drip
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('customer.smm.new-order', ['service_id' => $service->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-sm transition">
                                    <span>Order</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-3xl mb-2 text-slate-300"></i>
                                <p>No services matched your query.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($services->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $services->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
