@extends('layouts.admin')

@section('title', 'Platform Settings & Integration Keys')

@section('content')
<div class="max-w-4xl space-y-6">
    <div>
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">API Keys & Gateway Integrations</h2>
        <p class="text-xs text-slate-500">Configure AI provider keys (Gemini) and payment gateways (SantimPay, Telebirr, Chapa, PayPal, Stripe)</p>
    </div>

    <form action="{{ route('super-admin.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        <!-- AI Engine Settings -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="flex items-center space-x-2 pb-3 border-b border-slate-100">
                <i class="fa-solid fa-sparkles text-indigo-600"></i>
                <h3 class="font-bold text-sm text-slate-900 uppercase tracking-wider">AI Service Configuration (Google Gemini)</h3>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-slate-700 block mb-1">Google Gemini API Key</label>
                    <input type="password" name="gemini_api_key" value="{{ $settings['gemini_api_key'] ?? '' }}" placeholder="AIzaSy..." class="w-full text-xs font-mono border border-slate-300 rounded-lg p-2.5 outline-none focus:border-blue-600">
                    <span class="text-[10px] text-slate-400 mt-1 block">Leave empty to use intelligent local simulation driver.</span>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-700 block mb-1">Model Name</label>
                    <input type="text" name="gemini_model" value="{{ $settings['gemini_model'] ?? 'gemini-1.5-flash' }}" class="w-full text-xs font-mono border border-slate-300 rounded-lg p-2.5 outline-none">
                </div>
            </div>
        </div>

        <!-- Payment Gateway Settings -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
            <div class="flex items-center space-x-2 pb-3 border-b border-slate-100">
                <i class="fa-solid fa-credit-card text-emerald-600"></i>
                <h3 class="font-bold text-sm text-slate-900 uppercase tracking-wider">Payment Gateway Credentials</h3>
            </div>

            <!-- SantimPay -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center space-x-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>SantimPay (Ethiopia)</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-slate-600 block mb-1">Merchant ID</label>
                        <input type="text" name="santimpay_merchant_id" value="{{ $settings['santimpay_merchant_id'] ?? '' }}" placeholder="MERCHANT-..." class="w-full text-xs border border-slate-300 rounded-lg p-2 outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-600 block mb-1">Private Key / Secret</label>
                        <input type="password" name="santimpay_private_key" value="{{ $settings['santimpay_private_key'] ?? '' }}" placeholder="••••••••" class="w-full text-xs border border-slate-300 rounded-lg p-2 outline-none">
                    </div>
                </div>
            </div>

            <!-- Telebirr -->
            <div class="space-y-3 pt-3 border-t border-slate-100">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center space-x-1">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span>Telebirr H5 / Fabric</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs font-medium text-slate-600 block mb-1">App ID</label>
                        <input type="text" name="telebirr_app_id" value="{{ $settings['telebirr_app_id'] ?? '' }}" class="w-full text-xs border border-slate-300 rounded-lg p-2 outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-600 block mb-1">App Key</label>
                        <input type="password" name="telebirr_app_key" value="{{ $settings['telebirr_app_key'] ?? '' }}" class="w-full text-xs border border-slate-300 rounded-lg p-2 outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-600 block mb-1">Short Code</label>
                        <input type="text" name="telebirr_short_code" value="{{ $settings['telebirr_short_code'] ?? '' }}" class="w-full text-xs border border-slate-300 rounded-lg p-2 outline-none">
                    </div>
                </div>
            </div>

            <!-- Chapa -->
            <div class="space-y-3 pt-3 border-t border-slate-100">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center space-x-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Chapa Gateway</span>
                </h4>
                <div>
                    <label class="text-xs font-medium text-slate-600 block mb-1">Chapa Secret Key</label>
                    <input type="password" name="chapa_secret_key" value="{{ $settings['chapa_secret_key'] ?? '' }}" placeholder="CHASECK_TEST-..." class="w-full text-xs border border-slate-300 rounded-lg p-2 outline-none">
                </div>
            </div>

            <!-- PayPal & Stripe -->
            <div class="space-y-3 pt-3 border-t border-slate-100">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center space-x-1">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <span>PayPal & Stripe / Card</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-slate-600 block mb-1">PayPal Client ID</label>
                        <input type="text" name="paypal_client_id" value="{{ $settings['paypal_client_id'] ?? '' }}" class="w-full text-xs border border-slate-300 rounded-lg p-2 outline-none">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-600 block mb-1">Stripe Secret Key</label>
                        <input type="password" name="stripe_secret_key" value="{{ $settings['stripe_secret_key'] ?? '' }}" placeholder="sk_test_..." class="w-full text-xs border border-slate-300 rounded-lg p-2 outline-none">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl text-sm shadow transition">
            Save Integration Settings
        </button>
    </form>
</div>
@endsection
