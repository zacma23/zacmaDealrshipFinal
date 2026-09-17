@extends('layouts.app')

@section('title', 'Bulk SMM Orders')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <!-- Header -->
    <div>
        <div class="text-xs font-semibold text-blue-600 mb-1">
            <a href="{{ route('customer.smm.dashboard') }}" class="hover:underline">Dashboard</a> &bull;
            <span>Bulk Orders</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Bulk Order Processing</h1>
        <p class="text-sm text-slate-500">Place multiple social media campaigns simultaneously using batch syntax.</p>
    </div>

    @if(session('bulk_errors') && count(session('bulk_errors')) > 0)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 space-y-2 text-xs text-amber-900">
            <div class="font-bold flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                <span>Some lines could not be processed:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-slate-700">
                @foreach(session('bulk_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 sm:p-8 space-y-6">
        <form action="{{ route('customer.smm.bulk-orders.process') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                    Enter Bulk Order Lines (One order per line)
                </label>
                <p class="text-xs text-slate-500 mb-3">
                    Format: <code class="bg-slate-100 px-2 py-1 rounded text-slate-800 font-mono font-bold">service_id | target_link | quantity</code>
                </p>
                <textarea name="bulk_content"
                          rows="10"
                          required
                          placeholder="101 | https://instagram.com/p/xxx | 1000&#10;102 | https://tiktok.com/@user | 5000&#10;105 | https://youtube.com/watch?v=xxx | 2500"
                          class="w-full bg-slate-50 border border-slate-300 rounded-xl p-4 text-xs font-mono text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none leading-relaxed"></textarea>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="text-xs text-slate-600 space-y-0.5">
                    <div>Your Current Balance: <strong class="text-slate-900">${{ number_format($wallet->balance, 2) }}</strong></div>
                    <div class="text-[11px] text-slate-400">Orders are validated and processed in safe atomic batches.</div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('customer.smm.services') }}" target="_blank" class="text-xs font-bold text-blue-600 hover:underline">
                        View Service IDs &rarr;
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs px-6 py-2.5 rounded-xl shadow transition">
                        Process Bulk Orders
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
