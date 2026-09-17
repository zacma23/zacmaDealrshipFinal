@extends('layouts.app')

@section('title', 'Place New SMM Order')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="{
         platforms: {{ Js::from($platforms) }},
         selectedPlatformId: {{ $preselectedService ? $preselectedService->smm_platform_id : ($platforms->first()->id ?? 'null') }},
         selectedCategoryId: {{ $preselectedService ? $preselectedService->smm_category_id : 'null' }},
         selectedServiceId: {{ $preselectedService ? $preselectedService->id : 'null' }},
         quantity: {{ $preselectedService ? $preselectedService->min_quantity : 1000 }},
         target: '',
         dripFeed: false,
         runs: 5,
         interval: 60,
         walletBalance: {{ (float)$wallet->balance }},
         
         get currentPlatform() {
             return this.platforms.find(p => p.id == this.selectedPlatformId);
         },
         get currentCategories() {
             return this.currentPlatform ? this.currentPlatform.categories : [];
         },
         get currentCategory() {
             return this.currentCategories.find(c => c.id == this.selectedCategoryId);
         },
         get currentServices() {
             return this.currentCategory ? this.currentCategory.services : [];
         },
         get currentService() {
             if (!this.selectedServiceId) return null;
             for (let p of this.platforms) {
                 for (let c of p.categories) {
                     let s = c.services.find(item => item.id == this.selectedServiceId);
                     if (s) return s;
                 }
             }
             return null;
         },
         get ratePerK() {
             return this.currentService ? parseFloat(this.currentService.customer_price_per_k) : 0;
         },
         get totalCost() {
             if (!this.currentService || !this.quantity) return 0;
             let totalUnits = this.dripFeed && this.runs ? (this.quantity * this.runs) : this.quantity;
             return ((totalUnits / 1000) * this.ratePerK).toFixed(2);
         },
         get targetLabel() {
             if (!this.currentService) return 'Target URL or Username';
             let name = this.currentService.name.toLowerCase();
             if (name.includes('follower') || name.includes('member') || name.includes('subscriber')) {
                 return 'Profile / Channel Username or Link (@handle or URL)';
             }
             if (name.includes('like') || name.includes('view') || name.includes('play')) {
                 return 'Post / Video / Track Direct Link (URL)';
             }
             return 'Target Link or Handle';
         },
         init() {
             if (!this.selectedCategoryId && this.currentCategories.length > 0) {
                 this.selectedCategoryId = this.currentCategories[0].id;
             }
             if (!this.selectedServiceId && this.currentServices.length > 0) {
                 this.selectedServiceId = this.currentServices[0].id;
                 this.quantity = this.currentServices[0].min_quantity;
             }
         },
         onPlatformChange() {
             if (this.currentCategories.length > 0) {
                 this.selectedCategoryId = this.currentCategories[0].id;
                 this.onCategoryChange();
             } else {
                 this.selectedCategoryId = null;
                 this.selectedServiceId = null;
             }
         },
         onCategoryChange() {
             if (this.currentServices.length > 0) {
                 this.selectedServiceId = this.currentServices[0].id;
                 this.quantity = this.currentServices[0].min_quantity;
             } else {
                 this.selectedServiceId = null;
             }
         },
         onServiceChange() {
             if (this.currentService) {
                 this.quantity = Math.max(this.quantity, this.currentService.min_quantity);
             }
         }
     }"
     x-init="init()">

    <!-- Breadcrumb & Title -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold text-blue-600 mb-1">
                <a href="{{ route('customer.smm.dashboard') }}" class="hover:underline">Dashboard</a> &bull;
                <a href="{{ route('customer.smm.orders.index') }}" class="hover:underline">Orders</a> &bull;
                <span>New Campaign</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Create Social Media Campaign</h1>
        </div>
        <!-- Balance Badge -->
        <div class="bg-white border border-slate-200 rounded-xl px-4 py-2.5 shadow-sm flex items-center gap-3">
            <i class="fa-solid fa-wallet text-blue-600 text-lg"></i>
            <div>
                <div class="text-[10px] uppercase font-bold text-slate-400">Available Balance</div>
                <div class="text-sm font-black text-slate-900" :class="parseFloat(totalCost) > walletBalance ? 'text-rose-600' : 'text-emerald-600'">
                    ${{ number_format($wallet->balance, 2) }}
                </div>
            </div>
            <a href="{{ route('customer.smm.wallet') }}" class="ml-2 text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-2.5 py-1 rounded-lg">Top Up</a>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8">
        <form action="{{ route('customer.smm.orders.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- 1. Platform Selector Tabs -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">1. Select Platform</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
                    <template x-for="plat in platforms" :key="plat.id">
                        <button type="button"
                                @click="selectedPlatformId = plat.id; onPlatformChange()"
                                :class="selectedPlatformId == plat.id ? 'border-blue-600 bg-blue-50/70 text-blue-700 font-bold shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'"
                                class="border rounded-xl p-3 flex flex-col items-center justify-center gap-1.5 transition text-center text-xs">
                            <i :class="plat.icon" class="text-lg"></i>
                            <span x-text="plat.name" class="truncate max-w-[80px]"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- 2. Category Dropdown -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">2. Service Category</label>
                <select x-model="selectedCategoryId"
                        @change="onCategoryChange()"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <template x-for="cat in currentCategories" :key="cat.id">
                        <option :value="cat.id" x-text="cat.name"></option>
                    </template>
                </select>
            </div>

            <!-- 3. Specific Service Dropdown -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">3. Choose Exact Service</label>
                <select name="smm_service_id"
                        x-model="selectedServiceId"
                        @change="onServiceChange()"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <template x-for="s in currentServices" :key="s.id">
                        <option :value="s.id" x-text="'#' + s.id + ' — ' + s.name + ' ($' + parseFloat(s.customer_price_per_k).toFixed(2) + ' / 1k)'"></option>
                    </template>
                </select>
            </div>

            <!-- Service Details Callout Box -->
            <template x-if="currentService">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="bg-blue-100 text-blue-800 text-[11px] font-bold px-2.5 py-0.5 rounded-full" x-text="'Rate: $' + ratePerK.toFixed(2) + ' per 1,000'"></span>
                            <span class="bg-emerald-100 text-emerald-800 text-[11px] font-bold px-2.5 py-0.5 rounded-full" x-text="'Start: ' + currentService.start_time"></span>
                            <span class="bg-purple-100 text-purple-800 text-[11px] font-bold px-2.5 py-0.5 rounded-full" x-text="'Speed: ' + currentService.completion_time"></span>
                        </div>
                        <div class="text-[11px] font-semibold text-slate-500">
                            Min: <strong x-text="currentService.min_quantity"></strong> &bull; Max: <strong x-text="Number(currentService.max_quantity).toLocaleString()"></strong>
                        </div>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed" x-text="currentService.description"></p>
                    <div class="flex items-center gap-4 text-xs font-semibold text-slate-500">
                        <span class="flex items-center gap-1.5" :class="currentService.has_refill ? 'text-emerald-600' : 'text-slate-400'">
                            <i :class="currentService.has_refill ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark'"></i>
                            <span x-text="currentService.has_refill ? (currentService.refill_days + ' Days Refill Guarantee') : 'No Refill'"></span>
                        </span>
                        <span class="flex items-center gap-1.5" :class="currentService.has_cancel ? 'text-emerald-600' : 'text-slate-400'">
                            <i :class="currentService.has_cancel ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark'"></i>
                            <span x-text="currentService.has_cancel ? 'Cancellation Allowed' : 'No Cancellation'"></span>
                        </span>
                    </div>
                </div>
            </template>

            <!-- 4. Target Input -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5" x-text="targetLabel"></label>
                <div class="relative">
                    <i class="fa-solid fa-link absolute left-4 top-3.5 text-slate-400 text-sm"></i>
                    <input type="text"
                           name="target"
                           x-model="target"
                           required
                           placeholder="https://instagram.com/p/xxx or @handle"
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-11 pr-4 py-2.5 text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <p class="text-[11px] text-slate-400 mt-1">
                    <i class="fa-solid fa-shield-halved text-emerald-500 mr-1"></i>
                    We never ask for your passwords. Ensure your account/post is set to <strong>Public</strong>.
                </p>
            </div>

            <!-- 5. Quantity -->
            <div>
                <div class="flex justify-between items-center mb-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Quantity</label>
                    <span class="text-xs text-slate-400" x-show="currentService">
                        Min: <span x-text="currentService ? currentService.min_quantity : 0"></span> — Max: <span x-text="currentService ? Number(currentService.max_quantity).toLocaleString() : 0"></span>
                    </span>
                </div>
                <input type="number"
                       name="quantity"
                       x-model.number="quantity"
                       :min="currentService ? currentService.min_quantity : 1"
                       :max="currentService ? currentService.max_quantity : 10000000"
                       required
                       class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>

            <!-- 6. Optional Drip Feed -->
            <template x-if="currentService && currentService.dripfeed_supported">
                <div class="border border-slate-200 rounded-xl p-4 space-y-3 bg-slate-50/50">
                    <div class="flex items-center space-x-2">
                        <input type="checkbox"
                               id="drip_feed"
                               name="drip_feed"
                               value="1"
                               x-model="dripFeed"
                               class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                        <label for="drip_feed" class="text-xs font-bold text-slate-800 cursor-pointer">
                            Enable Drip-Feed (Spread delivery gradually over multiple runs)
                        </label>
                    </div>

                    <div x-show="dripFeed" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Number of Runs</label>
                            <input type="number" name="runs" x-model.number="runs" min="2" max="100" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-semibold">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Interval (minutes between runs)</label>
                            <input type="number" name="interval" x-model.number="interval" min="10" max="1440" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs font-semibold">
                        </div>
                    </div>
                </div>
            </template>

            <!-- Total Price Calculation Bar -->
            <div class="bg-gradient-to-r from-slate-900 to-blue-950 text-white rounded-xl p-5 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-md">
                <div>
                    <span class="text-xs text-slate-300 uppercase tracking-wider font-semibold">Total Campaign Charge</span>
                    <div class="text-3xl font-black text-white mt-0.5">
                        $<span x-text="totalCost"></span> <span class="text-xs font-normal text-blue-300">USD</span>
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1" x-show="dripFeed">
                        Drip-Feed: <span x-text="runs"></span> runs of <span x-text="quantity"></span> = <span x-text="quantity * runs"></span> total units
                    </div>
                </div>

                <div>
                    <template x-if="parseFloat(totalCost) <= walletBalance">
                        <button type="submit"
                                class="w-full sm:w-auto bg-blue-500 hover:bg-blue-600 text-white font-extrabold text-sm px-8 py-3 rounded-xl shadow-lg transition flex items-center justify-center space-x-2">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Submit Order Now</span>
                        </button>
                    </template>
                    <template x-if="parseFloat(totalCost) > walletBalance">
                        <div class="flex flex-col sm:items-end gap-1">
                            <a href="{{ route('customer.smm.wallet') }}"
                               class="w-full sm:w-auto bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-sm px-6 py-2.5 rounded-xl shadow transition flex items-center justify-center space-x-2">
                                <i class="fa-solid fa-wallet"></i>
                                <span>Add Funds to Wallet</span>
                            </a>
                            <span class="text-[11px] text-rose-300 font-semibold">Insufficient balance ($<span x-text="(totalCost - walletBalance).toFixed(2)"></span> needed)</span>
                        </div>
                    </template>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
