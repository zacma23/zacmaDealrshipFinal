@extends('layouts.app')

@section('title', 'SMM Services Directory')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold text-blue-600 mb-1">
                <a href="{{ route('home') }}" class="hover:underline">Home</a> &bull;
                <span>Services Directory</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Social Media Services Catalog</h1>
            <p class="text-sm text-slate-500">Live, verified services with transparent rates, delivery speeds, and refill policies.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('customer.smm.new-order') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-xl shadow transition flex items-center space-x-2">
                <i class="fa-solid fa-plus"></i>
                <span>Create Order</span>
            </a>
            <a href="{{ route('api-docs.public') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs sm:text-sm px-4 py-2.5 rounded-xl transition flex items-center space-x-2">
                <i class="fa-solid fa-code"></i>
                <span>API Docs</span>
            </a>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <form action="{{ url()->current() }}" method="GET" class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <!-- Search -->
            <div class="relative sm:col-span-2">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                <input type="text"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="Search services (e.g. Instagram Followers, TikTok Views...)"
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <!-- Platform Filter -->
            <div>
                <select name="platform"
                        onchange="this.form.submit()"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">All Platforms</option>
                    @foreach($platforms as $p)
                        <option value="{{ $p->slug }}" {{ request('platform') == $p->slug ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Sort Filter -->
            <div>
                <select name="sort"
                        onchange="this.form.submit()"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Default Sorting</option>
                    <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    <option value="min_qty" {{ request('sort') == 'min_qty' ? 'selected' : '' }}>Lowest Minimum</option>
                </select>
            </div>
        </div>
    </form>

    <!-- Services Table -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5">ID</th>
                        <th class="px-5 py-3.5">Service Details</th>
                        <th class="px-5 py-3.5">Rate / 1k</th>
                        <th class="px-5 py-3.5">Min / Max</th>
                        <th class="px-5 py-3.5">Start & Speed</th>
                        <th class="px-5 py-3.5">Refill Guarantee</th>
                        <th class="px-5 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($services as $s)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-4 font-mono font-bold text-slate-500">#{{ $s->id }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm shrink-0">
                                        <i class="{{ $s->platform->icon ?? 'fa-solid fa-layer-group' }}"></i>
                                    </div>
                                    <div class="space-y-1 max-w-sm">
                                        <div class="font-bold text-slate-900 text-xs">{{ $s->name }}</div>
                                        <div class="text-[11px] text-slate-500 line-clamp-1">{{ $s->description }}</div>
                                        <div class="flex items-center gap-2 text-[10px]">
                                            <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-medium">{{ $s->category->name ?? 'General' }}</span>
                                            <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-medium">{{ $s->quality_level }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-black text-slate-900 text-sm">
                                ${{ number_format($s->customer_price_per_k, 2) }}
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <div class="font-bold text-slate-800">{{ number_format($s->min_quantity) }}</div>
                                <div class="text-[10px] text-slate-400">up to {{ number_format($s->max_quantity) }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <div class="font-medium text-slate-800">{{ $s->start_time }}</div>
                                <div class="text-[10px] text-slate-400">{{ $s->completion_time }}</div>
                            </td>
                            <td class="px-5 py-4">
                                @if($s->has_refill)
                                    <span class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full text-[10px] font-bold">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span>{{ $s->refill_days }} Days Refill</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-slate-400 text-[10px] font-medium">
                                        <i class="fa-solid fa-circle-xmark"></i>
                                        <span>No Refill</span>
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('customer.smm.new-order', ['service_id' => $s->id]) }}"
                                   class="inline-flex items-center px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg transition shadow-sm">
                                    <span>Order</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-3xl text-slate-300 mb-2"></i>
                                <p class="text-sm">No services matched your query.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($services->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $services->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
