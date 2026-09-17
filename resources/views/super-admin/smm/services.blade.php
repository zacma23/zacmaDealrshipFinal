@extends('layouts.admin')

@section('title', 'Master SMM Services Catalog')

@section('content')
<div class="space-y-6" x-data="{ serviceModal: false, selectedPlatform: '' }">
    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Master Services Catalog</h2>
            <p class="text-xs text-slate-500 mt-0.5">Define platform services, wholesale costs, multi-tiered pricing, and upstream provider bindings.</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="serviceModal = true" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus-circle"></i> Add New Service
            </button>
        </div>
    </div>

    <!-- Filters Strip -->
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0">
            <a href="{{ route('super-admin.smm.services') }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap {{ !request('platform') ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                All Platforms
            </a>
            @foreach($platforms as $p)
                <a href="{{ route('super-admin.smm.services', ['platform' => $p->slug, 'q' => request('q')]) }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap flex items-center gap-1.5 {{ request('platform') === $p->slug ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <i class="{{ $p->icon }}"></i>
                    <span>{{ $p->name }}</span>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('super-admin.smm.services') }}" class="w-full md:w-72 flex items-center gap-2">
            @if(request('platform'))
                <input type="hidden" name="platform" value="{{ request('platform') }}">
            @endif
            <div class="relative w-full">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search ID, name, provider ID..." class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white">
            </div>
            <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs border border-slate-200">
                Filter
            </button>
        </form>
    </div>

    <!-- Master Services Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">ID</th>
                        <th class="py-3 px-4">Service & Platform</th>
                        <th class="py-3 px-4">Provider Mapping</th>
                        <th class="py-3 px-4">Wholesale Cost</th>
                        <th class="py-3 px-4">Reseller Rate</th>
                        <th class="py-3 px-4">Retail Rate</th>
                        <th class="py-3 px-4">Min / Max</th>
                        <th class="py-3 px-4">Features</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($services as $service)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-500">#{{ $service->id }}</td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                        <i class="{{ $service->platform->icon ?? 'fa-solid fa-share-nodes' }}"></i>
                                        {{ $service->platform->name ?? 'Universal' }}
                                    </span>
                                    <span class="text-[11px] text-slate-400 font-medium">{{ $service->category->name ?? '' }}</span>
                                </div>
                                <div class="font-bold text-slate-800 text-sm line-clamp-1">{{ $service->name }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                @if($service->provider)
                                    <div class="font-semibold text-slate-700">{{ $service->provider->name }}</div>
                                    <div class="text-[10px] text-slate-400">ID: {{ $service->provider_service_id ?? 'N/A' }}</div>
                                @else
                                    <span class="text-slate-400 italic">Manual Gateway</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold text-slate-600">
                                ${{ number_format($service->cost_per_k, 2) }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-blue-700">
                                ${{ number_format($service->reseller_price_per_k, 2) }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-emerald-700 text-sm">
                                ${{ number_format($service->customer_price_per_k, 2) }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-600">
                                {{ number_format($service->min_quantity) }} - {{ number_format($service->max_quantity) }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1">
                                    @if($service->has_refill)
                                        <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[10px] font-semibold">Refill</span>
                                    @endif
                                    @if($service->has_cancel)
                                        <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded text-[10px] font-semibold">Cancel</span>
                                    @endif
                                    @if($service->dripfeed_supported)
                                        <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded text-[10px] font-semibold">Drip</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $service->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($service->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-12 text-slate-400">
                                No services found in catalog.
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

    <!-- Create Service Modal -->
    <div x-show="serviceModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
        <div @click.away="serviceModal = false" class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 relative max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-bold text-slate-900 text-base">Add New Catalog Service</h3>
                <button @click="serviceModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form method="POST" action="{{ route('super-admin.smm.services.store') }}" class="space-y-4">
                @csrf

                <!-- Platform & Category -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Platform</label>
                        <select name="smm_platform_id" x-model="selectedPlatform" required class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Choose Platform --</option>
                            @foreach($platforms as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Category</label>
                        <select name="smm_category_id" required class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Choose Category --</option>
                            @foreach($platforms as $p)
                                <optgroup label="{{ $p->name }}">
                                    @foreach($p->categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Provider Binding -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Upstream Provider</label>
                        <select name="smm_provider_id" class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-lg">
                            <option value="">None (Manual Handling)</option>
                            @foreach($providers as $prov)
                                <option value="{{ $prov->id }}">{{ $prov->name }} ({{ $prov->adapter_type }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Provider Remote Service ID</label>
                        <input type="text" name="provider_service_id" placeholder="e.g. 1042" class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-lg">
                    </div>
                </div>

                <!-- Service Name -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Service Title</label>
                    <input type="text" name="name" required placeholder="Instagram Real Active Followers [Instant, 30D Refill]" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
                </div>

                <!-- Pricing Tiers per 1k -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Provider Cost / 1k ($)</label>
                        <input type="number" step="0.001" name="cost_per_k" required placeholder="0.50" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Reseller Rate / 1k ($)</label>
                        <input type="number" step="0.001" name="reseller_price_per_k" required placeholder="0.80" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Customer Rate / 1k ($)</label>
                        <input type="number" step="0.001" name="customer_price_per_k" required placeholder="1.20" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
                    </div>
                </div>

                <!-- Limits -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Min Quantity</label>
                        <input type="number" name="min_quantity" value="10" required class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Max Quantity</label>
                        <input type="number" name="max_quantity" value="100000" required class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
                    </div>
                </div>

                <!-- Feature Checkboxes -->
                <div class="grid grid-cols-3 gap-2 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="has_refill" value="1" class="w-4 h-4 text-blue-600 rounded">
                        <span>Refill Enabled</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="has_cancel" value="1" class="w-4 h-4 text-blue-600 rounded">
                        <span>Cancel Enabled</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="dripfeed_supported" value="1" class="w-4 h-4 text-blue-600 rounded">
                        <span>Dripfeed Supported</span>
                    </label>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Description & Guidelines</label>
                    <textarea name="description" rows="3" placeholder="Enter service terms, start time specifications, etc." class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" @click="serviceModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg text-xs">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-md">Create Service</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
