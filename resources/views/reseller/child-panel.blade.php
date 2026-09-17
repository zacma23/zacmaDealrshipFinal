@extends('layouts.admin')

@section('title', 'White-Label Child Panel Configuration')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold mb-1">
                <i class="fa-solid fa-wand-magic-sparkles"></i> White-Label SaaS Architecture
            </div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Child Panel & Domain Branding</h2>
            <p class="text-xs text-slate-500 mt-0.5">Launch your own branded SMM panel under your custom domain. Zacma handles order execution silently in the background.</p>
        </div>
        <div class="flex items-center gap-2">
            @if($org && $org->custom_domain)
                <a href="http://{{ $org->custom_domain }}" target="_blank" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs shadow-sm transition flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Visit Child Panel
                </a>
            @endif
        </div>
    </div>

    <!-- Main Settings Form & DNS Guide -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Configuration Form (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="font-bold text-slate-800 text-base mb-4 pb-3 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-sliders text-blue-600"></i> White-Label Settings
            </h3>

            <form method="POST" action="{{ route('reseller.child-panel.update') }}" class="space-y-5">
                @csrf

                <!-- Brand Name & Domain -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Panel Brand Name</label>
                        <input type="text" name="brand_name" value="{{ old('brand_name', $org->brand_name ?? $org->name ?? 'My Agency Panel') }}" required class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Custom Subdomain</label>
                        <div class="flex items-center">
                            <input type="text" name="subdomain" value="{{ old('subdomain', $org->subdomain ?? '') }}" placeholder="agency" class="w-full text-xs p-2.5 bg-slate-50 border border-r-0 border-slate-200 rounded-l-lg focus:ring-2 focus:ring-blue-500">
                            <span class="bg-slate-100 border border-l-0 border-slate-200 text-slate-500 text-xs px-3 py-2.5 rounded-r-lg font-mono">.zacma.com</span>
                        </div>
                    </div>
                </div>

                <!-- Custom Top-Level Domain -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Custom Apex / Subdomain Domain</label>
                    <div class="relative">
                        <i class="fa-solid fa-globe absolute left-3 top-3 text-xs text-slate-400"></i>
                        <input type="text" name="custom_domain" value="{{ old('custom_domain', $org->custom_domain ?? '') }}" placeholder="panel.youragency.com" class="w-full text-xs pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Leave blank if using the default platform subdomain.</p>
                </div>

                <!-- Automatic Pricing & Markup -->
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                    <div class="font-bold text-xs text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-percent text-purple-600"></i> Automated Customer Markup Rate
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Markup Type</label>
                            <select name="markup_type" class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="percentage" {{ ($org->markup_type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                <option value="fixed" {{ ($org->markup_type ?? '') === 'fixed' ? 'selected' : '' }}>Fixed Amount ($)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Default Markup Value</label>
                            <input type="number" step="0.1" name="default_markup" value="{{ old('default_markup', $org->default_markup ?? 20) }}" required class="w-full text-xs p-2.5 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500">This markup is automatically appended to your wholesale rates when your customers view and purchase services.</p>
                </div>

                <!-- Theme & Support Contacts -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Primary Theme Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="primary_color" value="{{ $org->theme_config['primary_color'] ?? '#2563EB' }}" class="w-10 h-9 p-0.5 rounded border border-slate-200 cursor-pointer">
                            <input type="text" readonly value="{{ $org->theme_config['primary_color'] ?? '#2563EB' }}" class="flex-1 text-xs p-2 bg-slate-50 border border-slate-200 rounded-lg font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Support Email</label>
                        <input type="email" name="support_email" value="{{ $org->contact_details['email'] ?? '' }}" placeholder="support@youragency.com" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">WhatsApp Support</label>
                        <input type="text" name="whatsapp" value="{{ $org->contact_details['whatsapp'] ?? '' }}" placeholder="+1234567890" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Telegram Handle / Channel</label>
                        <input type="text" name="telegram" value="{{ $org->contact_details['telegram'] ?? '' }}" placeholder="@YourAgencyBot" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
                    </div>
                </div>

                <!-- Registration Checkbox -->
                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="allow_public_registration" value="1" {{ ($org->allow_public_registration ?? true) ? 'checked' : '' }} class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                        <span class="text-xs font-semibold text-slate-700">Allow public customer self-registration on your child panel</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-md transition flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Save Child Panel Configuration
                    </button>
                </div>
            </form>
        </div>

        <!-- DNS & White-Label Setup Instructions (1 Col) -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h4 class="font-bold text-slate-800 text-sm mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-server text-blue-600"></i> DNS Setup Instructions
                </h4>
                <p class="text-xs text-slate-600 mb-3 leading-relaxed">
                    To point your custom domain to your Zacma child panel, add the following CNAME record in your domain registrar's DNS manager (e.g., Cloudflare, Namecheap, GoDaddy):
                </p>

                <div class="space-y-2 text-xs font-mono">
                    <div class="p-2.5 bg-slate-50 rounded border border-slate-200">
                        <span class="text-slate-400 block text-[10px]">RECORD TYPE</span>
                        <span class="font-bold text-slate-800">CNAME</span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded border border-slate-200">
                        <span class="text-slate-400 block text-[10px]">HOST / NAME</span>
                        <span class="font-bold text-slate-800">panel (or @)</span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded border border-slate-200">
                        <span class="text-slate-400 block text-[10px]">TARGET / VALUE</span>
                        <span class="font-bold text-blue-700">cname.zacma.com</span>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-emerald-50 rounded-lg border border-emerald-200 text-[11px] text-emerald-800 space-y-1">
                    <div class="font-bold flex items-center gap-1.5"><i class="fa-solid fa-lock"></i> Automatic SSL Certificate</div>
                    <p>Free Let's Encrypt SSL certificates are issued within minutes after DNS propagation completes.</p>
                </div>
            </div>

            <!-- Zero Leakage Guarantee -->
            <div class="bg-slate-900 text-white rounded-xl p-5 shadow-sm">
                <div class="text-amber-400 text-xs font-bold uppercase tracking-wider mb-1 flex items-center gap-1.5">
                    <i class="fa-solid fa-shield-halved"></i> Zero Leakage Isolation
                </div>
                <h5 class="font-bold text-sm">100% White-Labeled Experience</h5>
                <p class="text-slate-300 text-xs mt-1 leading-relaxed">
                    Your end clients will never see Zacma branding, upstream provider names, or wholesale costs. All transactional emails, invoices, and API responses strictly use your branding.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
