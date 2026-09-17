@extends('layouts.admin')

@section('title', 'White-Label Child Panels')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">White-Label Child Panels</h2>
            <p class="text-xs text-slate-500 mt-0.5">Multi-tenant child panel domains operating under independent branding with wholesale routing.</p>
        </div>
    </div>

    <!-- Child Panels Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Brand & Tenant</th>
                        <th class="py-3 px-4">Domain Routing</th>
                        <th class="py-3 px-4">Markup Rule</th>
                        <th class="py-3 px-4">Total Clients</th>
                        <th class="py-3 px-4">Support Channels</th>
                        <th class="py-3 px-4">Domain Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($childPanels as $panel)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-800 text-sm">{{ $panel->brand_name ?? $panel->name }}</div>
                                <div class="text-[11px] text-slate-400">Org: {{ $panel->name }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-600">
                                @if($panel->custom_domain)
                                    <span class="font-bold text-blue-600">{{ $panel->custom_domain }}</span>
                                @elseif($panel->subdomain)
                                    <span>{{ $panel->subdomain }}.zacma.com</span>
                                @else
                                    <span class="text-slate-400 italic">No domain configured</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-purple-700">
                                    +{{ $panel->default_markup ?? 20 }}{{ ($panel->markup_type ?? 'percentage') === 'percentage' ? '%' : '$' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700">
                                {{ $panel->users_count ?? 0 }} clients
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                @php
                                    $contacts = $panel->contact_details ?? [];
                                @endphp
                                <div class="space-y-0.5 text-[11px]">
                                    @if(!empty($contacts['email']))
                                        <div><i class="fa-solid fa-envelope text-slate-400 mr-1"></i> {{ $contacts['email'] }}</div>
                                    @endif
                                    @if(!empty($contacts['whatsapp']))
                                        <div><i class="fa-brands fa-whatsapp text-emerald-500 mr-1"></i> {{ $contacts['whatsapp'] }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $panel->custom_domain_status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $panel->custom_domain_status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ ucfirst($panel->custom_domain_status ?? 'inactive') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-400">
                                No child panels registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($childPanels->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $childPanels->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
