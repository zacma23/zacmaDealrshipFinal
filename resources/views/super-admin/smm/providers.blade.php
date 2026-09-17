@extends('layouts.admin')

@section('title', 'Upstream SMM API Gateways & Providers')

@section('content')
<div class="space-y-6" x-data="{ providerModal: false }">
    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Upstream Provider Gateways</h2>
            <p class="text-xs text-slate-500 mt-0.5">Integrate wholesale SMM suppliers, check remote balances, and configure failover redundancy.</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="providerModal = true" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus-circle"></i> Connect New Gateway
            </button>
        </div>
    </div>

    <!-- Providers Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Provider</th>
                        <th class="py-3 px-4">API Endpoint</th>
                        <th class="py-3 px-4">Adapter</th>
                        <th class="py-3 px-4">Remote Balance</th>
                        <th class="py-3 px-4">Mapped Services</th>
                        <th class="py-3 px-4">Total Orders</th>
                        <th class="py-3 px-4">Status & Health</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($providers as $provider)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-800 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs">
                                        <i class="fa-solid fa-network-wired"></i>
                                    </span>
                                    <span>{{ $provider->name }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-500 max-w-xs truncate">
                                {{ $provider->api_url }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded font-mono text-[10px] font-bold uppercase">
                                    {{ $provider->adapter_type }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900 text-sm">
                                ${{ number_format($provider->balance, 2) }}
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700">
                                {{ $provider->services_count ?? 0 }} services
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700">
                                {{ $provider->orders_count ?? 0 }} orders
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full {{ $provider->health_status === 'healthy' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                    <span class="font-bold text-slate-700 capitalize text-[11px]">{{ $provider->health_status }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('super-admin.smm.providers.test', $provider) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs transition border border-slate-200">
                                    <i class="fa-solid fa-plug"></i> Test Link
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                No upstream providers configured.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Connect Provider Modal -->
    <div x-show="providerModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
        <div @click.away="providerModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 relative">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-bold text-slate-900 text-base">Connect Wholesale Gateway</h3>
                <button @click="providerModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form method="POST" action="{{ route('super-admin.smm.providers.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Provider Name</label>
                    <input type="text" name="name" required placeholder="FastSMM Gateway" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Adapter Protocol</label>
                    <select name="adapter_type" required class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="standard_v2">Standard SMM API v2 (MoreThanPanel, JAP, etc.)</option>
                        <option value="mock_sandbox">Internal Mock Sandbox (Instant Simulated Delivery)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">API Endpoint URL</label>
                    <input type="url" name="api_url" required placeholder="https://provider.com/api/v2" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">API Key / Token</label>
                    <input type="password" name="api_key" required placeholder="Enter supplier API token" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="providerModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg text-xs">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-md">Connect Provider</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
