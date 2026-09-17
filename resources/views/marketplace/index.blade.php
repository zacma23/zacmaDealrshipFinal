@extends('layouts.app')

@section('title', 'Zacma SMM | #1 Wholesale Social Media Marketing Marketplace & Reseller Cloud')

@section('content')
<!-- Hero Section -->
<section class="relative bg-slate-950 text-white overflow-hidden py-20 lg:py-28 border-b border-slate-800">
    <!-- Glow Background Accents -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[450px] bg-gradient-to-tr from-blue-600/25 via-indigo-600/20 to-purple-600/20 blur-3xl rounded-full pointer-events-none"></div>
    <div class="absolute inset-0 opacity-15 bg-[radial-gradient(#3b82f6_1px,transparent_1px)] [background-size:24px_24px]"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto space-y-6">
            <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20 backdrop-blur-md shadow-sm">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>The Global Wholesale SMM Provider & Reseller Cloud</span>
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight">
                Scale Your Social Presence at <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-indigo-300 to-cyan-300">Wholesale Rates</span>
            </h1>

            <p class="text-base sm:text-lg text-slate-300 leading-relaxed max-w-2xl mx-auto">
                Direct upstream gateway for Instagram, TikTok, YouTube, Telegram, Facebook, X, and Spotify. Built with automated double-entry ledger accounting, SMM v2 REST API, and turnkey white-label child panels.
            </p>

            <!-- CTA Actions -->
            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                <a href="{{ route('customer.smm.new-order') }}" class="px-7 py-3.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl text-sm shadow-lg shadow-blue-600/30 transition flex items-center space-x-2">
                    <i class="fa-solid fa-bolt text-amber-300"></i>
                    <span>Place Instant Order</span>
                </a>
                <a href="#services-catalog" class="px-7 py-3.5 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-sm border border-slate-700 transition flex items-center space-x-2">
                    <i class="fa-solid fa-list-check text-blue-400"></i>
                    <span>View Services Catalog</span>
                </a>
                <a href="{{ route('api-docs.public') }}" class="px-5 py-3.5 bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white font-semibold rounded-xl text-sm border border-white/10 transition flex items-center space-x-2">
                    <i class="fa-solid fa-code text-indigo-400"></i>
                    <span>Reseller API</span>
                </a>
            </div>

            <!-- Live Trust Counters -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-10 border-t border-slate-800/80 text-left">
                <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800 backdrop-blur-sm">
                    <div class="text-2xl lg:text-3xl font-extrabold text-white">{{ number_format($stats['total_orders']) }}+</div>
                    <div class="text-xs font-medium text-slate-400 mt-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-check-double text-emerald-400"></i> Orders Delivered
                    </div>
                </div>
                <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800 backdrop-blur-sm">
                    <div class="text-2xl lg:text-3xl font-extrabold text-blue-400">{{ number_format($stats['total_users']) }}+</div>
                    <div class="text-xs font-medium text-slate-400 mt-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-users text-blue-400"></i> Resellers & Agencies
                    </div>
                </div>
                <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800 backdrop-blur-sm">
                    <div class="text-2xl lg:text-3xl font-extrabold text-emerald-400">{{ $stats['avg_speed'] }}</div>
                    <div class="text-xs font-medium text-slate-400 mt-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-gauge-high text-emerald-400"></i> Average Start Time
                    </div>
                </div>
                <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800 backdrop-blur-sm">
                    <div class="text-2xl lg:text-3xl font-extrabold text-indigo-400">{{ $stats['uptime'] }}</div>
                    <div class="text-xs font-medium text-slate-400 mt-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-server text-indigo-400"></i> API Gateway Health
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Supported Social Platforms Ribbon -->
<section class="bg-slate-900 border-b border-slate-800/80 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4 overflow-x-auto py-2 scrollbar-none">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 shrink-0">Supported Networks:</span>
            @foreach($platforms as $p)
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800/60 border border-slate-700/60 shrink-0 text-slate-200 text-xs font-semibold">
                    <i class="{{ $p->icon ?: 'fa-solid fa-share-nodes' }} text-blue-400 text-sm"></i>
                    <span>{{ $p->name }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Live Interactive Price Calculator -->
<section class="py-14 bg-slate-900/40 border-b border-slate-800" x-data="{
    platformId: '{{ $platforms->first()?->id ?? 1 }}',
    services: {{ Js::from($featuredServices) }},
    selectedServiceId: {{ $featuredServices->first()?->id ?? 0 }},
    quantity: 1000,
    get currentService() {
        return this.services.find(s => s.id == this.selectedServiceId) || this.filteredServices[0] || null;
    },
    get filteredServices() {
        return this.services.filter(s => s.smm_platform_id == this.platformId);
    },
    get totalPrice() {
        if (!this.currentService) return '0.00';
        let rate = parseFloat(this.currentService.customer_price_per_k);
        let qty = parseInt(this.quantity) || 0;
        return ((qty / 1000) * rate).toFixed(2);
    }
}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-3xl p-6 sm:p-10 border border-slate-700/80 shadow-2xl relative overflow-hidden">
            <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-5 space-y-4 text-white">
                    <span class="text-xs font-bold text-blue-400 uppercase tracking-wider bg-blue-500/10 px-3 py-1 rounded-full border border-blue-400/20 inline-block">
                        Instant Quote Calculator
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight leading-tight">
                        Check Wholesale Rates Before Ordering
                    </h2>
                    <p class="text-slate-300 text-sm leading-relaxed">
                        Select your platform and package to calculate live rates with zero hidden markups. Funds are deducted atomically from your digital wallet only upon successful upstream dispatch.
                    </p>
                    <ul class="space-y-2 text-xs text-slate-300 pt-2">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-emerald-400"></i> Automated refill protection included</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-emerald-400"></i> No passwords or account access required</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-emerald-400"></i> Real-time order progress tracking</li>
                    </ul>
                </div>

                <div class="lg:col-span-7 bg-slate-950/80 rounded-2xl p-6 border border-slate-700 space-y-5">
                    <!-- Platform Selector -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">1. Select Social Platform</label>
                        <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-none">
                            @foreach($platforms as $p)
                                <button type="button" @click="platformId = '{{ $p->id }}'; selectedServiceId = filteredServices[0]?.id" 
                                    :class="platformId == '{{ $p->id }}' ? 'bg-blue-600 text-white border-blue-500' : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'"
                                    class="px-3.5 py-2 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 shrink-0">
                                    <i class="{{ $p->icon ?: 'fa-solid fa-share-nodes' }}"></i>
                                    <span>{{ $p->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Service Dropdown -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">2. Select Service Package</label>
                        <select x-model="selectedServiceId" class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-3.5 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <template x-for="s in filteredServices" :key="s.id">
                                <option :value="s.id" x-text="`${s.name} — $${parseFloat(s.customer_price_per_k).toFixed(2)} / 1k`"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Quantity Input -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-xs font-bold text-slate-400 uppercase">3. Quantity</label>
                            <span class="text-[11px] text-slate-400" x-show="currentService" x-text="`Min: ${currentService?.min_quantity?.toLocaleString()} | Max: ${currentService?.max_quantity?.toLocaleString()}`"></span>
                        </div>
                        <input type="number" x-model.number="quantity" step="100" class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-3.5 py-2.5 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>

                    <!-- Live Price Summary & Order Button -->
                    <div class="p-4 bg-slate-900/90 rounded-xl border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div>
                            <span class="text-[11px] text-slate-400 uppercase tracking-wider block font-semibold">Total Estimated Cost:</span>
                            <div class="text-2xl sm:text-3xl font-extrabold text-emerald-400 font-mono">
                                $<span x-text="totalPrice"></span> <span class="text-xs font-normal text-slate-400">USD</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <a :href="`{{ route('customer.smm.new-order') }}?service_id=${currentService?.id}`" class="w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl shadow-lg transition text-center flex items-center justify-center gap-2">
                                <i class="fa-solid fa-cart-shopping"></i>
                                <span>Order This Service</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Live Services Catalog Table -->
<section id="services-catalog" class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{
    activeTab: 'all',
    searchQuery: '',
    matches(service) {
        if (this.activeTab !== 'all' && service.platform?.slug !== this.activeTab) return false;
        if (!this.searchQuery) return true;
        let q = this.searchQuery.toLowerCase();
        return (service.name && service.name.toLowerCase().includes(q))
            || (service.category?.name && service.category.name.toLowerCase().includes(q))
            || (service.platform?.name && service.platform.name.toLowerCase().includes(q));
    }
}">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-600 border border-blue-200 mb-2">
                <i class="fa-solid fa-list-check"></i>
                <span>Live Public Rate List</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Wholesale Services Catalog
            </h2>
            <p class="text-sm text-slate-500 mt-1">Direct API delivery nodes with live rate updates and refill safeguards.</p>
        </div>

        <!-- Search input -->
        <div class="w-full md:w-72">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                <input type="text" x-model="searchQuery" placeholder="Filter services..." class="w-full bg-white border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
        </div>
    </div>

    <!-- Platform Filter Tabs -->
    <div class="flex gap-2 overflow-x-auto pb-3 mb-6 scrollbar-none border-b border-slate-200">
        <button type="button" @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap">
            All Platforms ({{ $featuredServices->count() }})
        </button>
        @foreach($platforms as $p)
            <button type="button" @click="activeTab = '{{ $p->slug }}'" :class="activeTab === '{{ $p->slug }}' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap flex items-center gap-1.5">
                <i class="{{ $p->icon ?: 'fa-solid fa-share-nodes' }}"></i>
                <span>{{ $p->name }}</span>
            </button>
        @endforeach
    </div>

    <!-- Table Container -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-4">ID</th>
                        <th class="py-3.5 px-4">Platform & Service</th>
                        <th class="py-3.5 px-4">Rate / 1k</th>
                        <th class="py-3.5 px-4">Min / Max</th>
                        <th class="py-3.5 px-4">Speed / Start</th>
                        <th class="py-3.5 px-4">Features</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($featuredServices as $service)
                        <tr class="hover:bg-slate-50/80 transition" x-show="matches({{ Js::from($service) }})">
                            <td class="py-3 px-4 font-mono font-bold text-slate-400">#{{ $service->id }}</td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 text-xs">
                                        <i class="{{ $service->platform?->icon ?: 'fa-solid fa-bolt' }}"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $service->name }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $service->platform?->name }} &bull; {{ $service->category?->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 font-mono font-extrabold text-blue-600 text-sm">
                                ${{ number_format($service->customer_price_per_k, 2) }}
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-600">
                                {{ number_format($service->min_quantity) }} &mdash; {{ number_format($service->max_quantity) }}
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                <span class="inline-flex items-center gap-1 font-semibold text-emerald-600">
                                    <i class="fa-solid fa-bolt text-[10px]"></i>
                                    {{ $service->start_time ?? '0-1 Hours' }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-1">
                                    @if($service->has_refill)
                                        <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[10px] font-bold" title="30-Day Guaranteed Refill">Refill</span>
                                    @endif
                                    @if($service->has_cancel)
                                        <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded text-[10px] font-bold">Cancel</span>
                                    @endif
                                    @if($service->dripfeed_supported)
                                        <span class="px-1.5 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 rounded text-[10px] font-bold">Drip</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('customer.smm.new-order', ['service_id' => $service->id]) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-sm transition">
                                    <span>Order</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Wholesale Reseller SaaS & White-Label Child Panels -->
<section class="py-20 bg-slate-900 text-white relative overflow-hidden border-t border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            <div class="lg:col-span-6 space-y-6">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                    <i class="fa-solid fa-cloud"></i>
                    <span>Turnkey Agency SaaS Architecture</span>
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                    Start Your Own SMM Panel in Under <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-300">5 Minutes</span>
                </h2>
                <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                    With Zacma White-Label Child Panels, you can sell social media marketing services under your own domain name, with your own logo, branding colors, and automatic percentage markup.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div class="p-4 rounded-xl bg-slate-800/80 border border-slate-700/80 space-y-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-sm">
                            <i class="fa-solid fa-globe"></i>
                        </div>
                        <h3 class="font-bold text-sm text-white">Custom Domain Mapping</h3>
                        <p class="text-xs text-slate-400">Point <code>panel.youragency.com</code> with automated SSL and isolated tenant accounts.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-800/80 border border-slate-700/80 space-y-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-sm">
                            <i class="fa-solid fa-percent"></i>
                        </div>
                        <h3 class="font-bold text-sm text-white">Automated Profit Markup</h3>
                        <p class="text-xs text-slate-400">Set a global markup (e.g. +35%). All prices adjust automatically with zero manual updates.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-800/80 border border-slate-700/80 space-y-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-sm">
                            <i class="fa-solid fa-mask"></i>
                        </div>
                        <h3 class="font-bold text-sm text-white">Zero Supplier Leakage</h3>
                        <p class="text-xs text-slate-400">Upstream provider identities and costs are strictly masked from your end clients.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-800/80 border border-slate-700/80 space-y-2">
                        <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-sm">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <h3 class="font-bold text-sm text-white">Automated Fulfillment</h3>
                        <p class="text-xs text-slate-400">Your clients order on your panel &rarr; orders dispatch through Zacma wholesale nodes instantly.</p>
                    </div>
                </div>

                <div class="pt-4">
                    <a href="{{ route('reseller.child-panel') }}" class="px-6 py-3.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-xl text-sm shadow-xl transition inline-flex items-center gap-2">
                        <i class="fa-solid fa-rocket"></i>
                        <span>Configure Child Panel Now</span>
                    </a>
                </div>
            </div>

            <!-- API Code Mockup Preview -->
            <div class="lg:col-span-6 bg-slate-950 rounded-2xl p-6 border border-slate-800 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <span class="text-xs font-mono text-slate-400 ml-2">POST /api/v2</span>
                    </div>
                    <span class="text-[11px] font-mono text-blue-400 bg-blue-900/40 px-2 py-0.5 rounded border border-blue-500/20">SMM v2 Protocol</span>
                </div>

                <pre class="font-mono text-xs text-slate-300 leading-relaxed overflow-x-auto p-2"><code><span class="text-slate-500"># Send instant order via cURL</span>
curl -X POST {{ url('/api/v2') }} \
  -d <span class="text-emerald-400">"key=YOUR_SECRET_API_KEY"</span> \
  -d <span class="text-emerald-400">"action=add"</span> \
  -d <span class="text-emerald-400">"service=1"</span> \
  -d <span class="text-emerald-400">"link=https://instagram.com/yourhandle"</span> \
  -d <span class="text-emerald-400">"quantity=1000"</span>

<span class="text-slate-500"># Instant JSON Response (HTTP 200)</span>
{
  <span class="text-blue-400">"order"</span>: 104829
}</code></pre>

                <div class="pt-2 border-t border-slate-800 flex justify-between items-center text-xs">
                    <span class="text-slate-400">Includes rate limiting & IP whitelisting</span>
                    <a href="{{ route('api-docs.public') }}" class="text-blue-400 hover:text-blue-300 font-bold flex items-center gap-1">
                        <span>Read Full Documentation</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Features Grid -->
<section class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-2xl mx-auto mb-14 space-y-3">
        <span class="text-xs font-bold text-blue-600 uppercase tracking-wider bg-blue-50 px-3 py-1 rounded-full border border-blue-200 inline-block">
            Enterprise Reliability
        </span>
        <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">
            Engineered for Maximum Scale & Zero Downtime
        </h2>
        <p class="text-sm text-slate-500">
            Unlike legacy panels that rely on manual dispatch, Zacma executes through audited serverless nodes and atomic ledger accounting.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm hover:shadow-md transition space-y-3">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900">Atomic Financial Ledger</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                Pessimistic row locking (<code>lockForUpdate</code>) prevents double-spends and balance desynchronization. Every transaction is immutably audited.
            </p>
        </div>

        <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm hover:shadow-md transition space-y-3">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-arrow-rotate-left"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900">Automated Partial Refunds</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                If an upstream provider delivers partially or cancels an order, unfulfilled quantities are calculated and returned to your wallet balance instantly.
            </p>
        </div>

        <div class="bg-white rounded-2xl p-7 border border-slate-200 shadow-sm hover:shadow-md transition space-y-3">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-headset"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900">24/7 Priority Support Desk</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                Direct in-app ticketing linked to specific order numbers ensures fast resolutions, refill accelerations, and technical guidance.
            </p>
        </div>
    </div>
</section>

<!-- Frequently Asked Questions -->
<section class="py-16 bg-slate-100/70 border-t border-slate-200" x-data="{ openFaq: null }">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 space-y-2">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Frequently Asked Questions</h2>
            <p class="text-sm text-slate-500">Everything you need to know about ordering and reselling on Zacma.</p>
        </div>

        <div class="space-y-3">
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <button type="button" @click="openFaq = openFaq === 1 ? null : 1" class="w-full text-left p-5 flex justify-between items-center text-sm font-bold text-slate-800">
                    <span>What is an SMM panel and how does it work?</span>
                    <i class="fa-solid fa-chevron-down text-xs transition" :class="openFaq === 1 ? 'rotate-180 text-blue-600' : 'text-slate-400'"></i>
                </button>
                <div x-show="openFaq === 1" x-cloak class="p-5 pt-0 text-xs text-slate-600 leading-relaxed border-t border-slate-100">
                    An SMM (Social Media Marketing) panel is an online platform that provides automated social media growth services (such as followers, likes, views, and engagement) at wholesale prices. Users deposit funds into their wallet and submit public URLs without sharing passwords.
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <button type="button" @click="openFaq = openFaq === 2 ? null : 2" class="w-full text-left p-5 flex justify-between items-center text-sm font-bold text-slate-800">
                    <span>How fast are orders processed and delivered?</span>
                    <i class="fa-solid fa-chevron-down text-xs transition" :class="openFaq === 2 ? 'rotate-180 text-blue-600' : 'text-slate-400'"></i>
                </button>
                <div x-show="openFaq === 2" x-cloak class="p-5 pt-0 text-xs text-slate-600 leading-relaxed border-t border-slate-100">
                    Most orders begin processing in under 60 seconds thanks to our direct serverless gateway nodes. Delivery speeds depend on package size and service type to ensure authentic, organic delivery curves.
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <button type="button" @click="openFaq = openFaq === 3 ? null : 3" class="w-full text-left p-5 flex justify-between items-center text-sm font-bold text-slate-800">
                    <span>Can I start my own business using your Child Panels?</span>
                    <i class="fa-solid fa-chevron-down text-xs transition" :class="openFaq === 3 ? 'rotate-180 text-blue-600' : 'text-slate-400'"></i>
                </button>
                <div x-show="openFaq === 3" x-cloak class="p-5 pt-0 text-xs text-slate-600 leading-relaxed border-t border-slate-100">
                    Yes! You can connect your custom domain (e.g. <code>panel.yourcompany.com</code>) directly. Your customers register on your panel and pay your retail rates. We fulfill the orders automatically at our wholesale rates, and you keep 100% of the markup.
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <button type="button" @click="openFaq = openFaq === 4 ? null : 4" class="w-full text-left p-5 flex justify-between items-center text-sm font-bold text-slate-800">
                    <span>What happens if an order drops or fails?</span>
                    <i class="fa-solid fa-chevron-down text-xs transition" :class="openFaq === 4 ? 'rotate-180 text-blue-600' : 'text-slate-400'"></i>
                </button>
                <div x-show="openFaq === 4" x-cloak class="p-5 pt-0 text-xs text-slate-600 leading-relaxed border-t border-slate-100">
                    Our platform monitors delivery statuses automatically. If an order fails, funds are refunded to your digital wallet 100%. If partial delivery occurs, the unfulfilled quantity is prorated and refunded. Services with refill protection can be refilled with a single click.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Footer Banner -->
<section class="py-16 bg-blue-600 text-white text-center relative overflow-hidden">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 space-y-6">
        <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">
            Ready to Accelerate Your Social Growth?
        </h2>
        <p class="text-blue-100 text-sm sm:text-base max-w-xl mx-auto">
            Create an account in 30 seconds. Top up with your preferred payment method and access over 14+ verified wholesale services.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('register') }}" class="px-8 py-3.5 bg-white text-blue-700 hover:bg-blue-50 font-bold rounded-xl text-sm shadow-xl transition">
                Create Free Account
            </a>
            <a href="{{ route('customer.smm.new-order') }}" class="px-8 py-3.5 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded-xl text-sm border border-blue-500 shadow-md transition">
                Place New Order &rarr;
            </a>
        </div>
    </div>
</section>
@endsection
