@extends('layouts.app')

@section('title', 'My Digital Wallet')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{ depositOpen: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold text-blue-600 mb-1">
                <a href="{{ route('customer.smm.dashboard') }}" class="hover:underline">Dashboard</a> &bull;
                <span>Digital Wallet</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Wallet & Financial Ledger</h1>
            <p class="text-sm text-slate-500">Atomic balance management, instant top-ups, and double-entry transaction audit history.</p>
        </div>
        <button @click="depositOpen = true"
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm px-6 py-2.5 rounded-xl shadow-lg transition flex items-center space-x-2">
            <i class="fa-solid fa-plus-circle"></i>
            <span>Deposit Funds</span>
        </button>
    </div>

    <!-- Wallet Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-slate-900 to-blue-950 text-white rounded-2xl p-6 shadow-xl relative overflow-hidden">
            <div class="relative z-10">
                <span class="text-xs font-semibold uppercase tracking-wider text-blue-300">Available Balance</span>
                <h3 class="text-3xl font-black mt-1">${{ number_format($wallet->balance, 2) }} <span class="text-xs font-normal text-blue-300">{{ $wallet->currency }}</span></h3>
                <div class="mt-4 flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold uppercase {{ $wallet->status === 'active' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300' }}">
                        {{ $wallet->status }}
                    </span>
                    <span class="text-[11px] text-slate-400">Atomic Ledger Guard</span>
                </div>
            </div>
            <i class="fa-solid fa-wallet text-6xl text-white/5 absolute -right-3 -bottom-3"></i>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Deposits</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">${{ number_format($wallet->total_deposited, 2) }}</h3>
                <span class="text-xs text-slate-400">Lifetime credited</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-arrow-down"></i>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Spent</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">${{ number_format($wallet->total_spent, 2) }}</h3>
                <span class="text-xs text-slate-400">Campaign orders</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-arrow-up"></i>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Refunded</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">${{ number_format($wallet->total_refunded, 2) }}</h3>
                <span class="text-xs text-slate-400">Prorated & cancelled</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-rotate-left"></i>
            </div>
        </div>
    </div>

    <!-- Deposit Modal -->
    <div x-show="depositOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.outside="depositOpen = false" class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i class="fa-solid fa-circle-dollar-to-slot"></i>
                    </div>
                    <h3 class="font-extrabold text-slate-900 text-lg">Deposit Funds into Wallet</h3>
                </div>
                <button @click="depositOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('customer.smm.wallet.deposit') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Amount & Currency -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Amount</label>
                        <input type="number" step="0.01" min="1" max="10000" name="amount" value="25.00" required
                               class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-black text-slate-900 outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Currency</label>
                        <select name="currency" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-sm font-bold text-slate-900 outline-none">
                            <option value="USD">USD ($)</option>
                            <option value="ETB">ETB (Br)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                        </select>
                    </div>
                </div>

                <!-- Payment Gateway Selector -->
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-2">Select Payment Gateway</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        @php
                            $gateways = [
                                ['id' => 'chapa', 'name' => 'Chapa', 'desc' => 'Cards & Mobile Money', 'icon' => 'fa-solid fa-credit-card'],
                                ['id' => 'telebirr', 'name' => 'Telebirr', 'desc' => 'Ethiopian Mobile Money', 'icon' => 'fa-solid fa-mobile-screen'],
                                ['id' => 'paypal', 'name' => 'PayPal', 'desc' => 'International & Balance', 'icon' => 'fa-brands fa-paypal'],
                                ['id' => 'card', 'name' => 'Credit / Debit', 'desc' => 'Visa / Mastercard', 'icon' => 'fa-regular fa-credit-card'],
                                ['id' => 'santimpay', 'name' => 'SantimPay', 'desc' => 'Direct Bank Checkout', 'icon' => 'fa-solid fa-building-columns'],
                                ['id' => 'cash', 'name' => 'Sandbox / Cash', 'desc' => 'Instant Demo Top-up', 'icon' => 'fa-solid fa-vial'],
                            ];
                        @endphp
                        @foreach($gateways as $gw)
                            <label class="cursor-pointer">
                                <input type="radio" name="provider" value="{{ $gw['id'] }}" class="peer sr-only" {{ $loop->first ? 'checked' : '' }}>
                                <div class="p-3 border border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50/50 rounded-xl transition flex flex-col items-center text-center space-y-1">
                                    <i class="{{ $gw['icon'] }} text-base text-slate-600 peer-checked:text-emerald-600"></i>
                                    <span class="text-xs font-bold text-slate-800">{{ $gw['name'] }}</span>
                                    <span class="text-[9px] text-slate-400 truncate max-w-full">{{ $gw['desc'] }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-[11px] text-slate-600 flex items-center gap-2">
                    <i class="fa-solid fa-lock text-emerald-600"></i>
                    <span>Transactions are secured via 256-bit encryption. Funds are credited instantly upon gateway confirmation.</span>
                </div>

                <button type="submit"
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-sm py-3 rounded-xl shadow-lg transition flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-bolt"></i>
                    <span>Proceed to Payment</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Immutable Double-Entry Transaction Ledger -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden space-y-4">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                    <i class="fa-solid fa-book-journal-whills text-blue-600"></i>
                    <span>Transaction Ledger</span>
                </h3>
                <p class="text-xs text-slate-400">Complete historical financial audit log of debits, credits, and refunds.</p>
            </div>
            <span class="text-xs font-semibold bg-slate-100 text-slate-700 px-3 py-1 rounded-full">
                {{ $transactions->total() }} Records
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5">Reference</th>
                        <th class="px-5 py-3.5">Type</th>
                        <th class="px-5 py-3.5">Amount</th>
                        <th class="px-5 py-3.5">Balance Change</th>
                        <th class="px-5 py-3.5">Description</th>
                        <th class="px-5 py-3.5">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $txn)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-4 font-mono font-bold text-slate-800">{{ $txn->reference }}</td>
                            <td class="px-5 py-4">
                                @php
                                    $typeBadge = match($txn->type) {
                                        'deposit' => 'bg-emerald-100 text-emerald-800',
                                        'order_charge' => 'bg-blue-100 text-blue-800',
                                        'order_refund' => 'bg-purple-100 text-purple-800',
                                        'manual_credit' => 'bg-teal-100 text-teal-800',
                                        'manual_debit' => 'bg-amber-100 text-amber-800',
                                        'affiliate_commission' => 'bg-indigo-100 text-indigo-800',
                                        default => 'bg-slate-100 text-slate-800',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $typeBadge }}">
                                    {{ str_replace('_', ' ', $txn->type) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 font-black text-sm {{ $txn->amount >= 0 ? 'text-emerald-600' : 'text-slate-900' }}">
                                {{ $txn->amount >= 0 ? '+' : '' }}${{ number_format($txn->amount, 2) }}
                            </td>
                            <td class="px-5 py-4 text-slate-500 text-[11px] font-mono">
                                ${{ number_format($txn->balance_before, 2) }} &rarr; ${{ number_format($txn->balance_after, 2) }}
                            </td>
                            <td class="px-5 py-4 text-slate-700 max-w-xs truncate" title="{{ $txn->description }}">
                                {{ $txn->description }}
                            </td>
                            <td class="px-5 py-4 text-slate-500 text-[11px] whitespace-nowrap">
                                {{ $txn->created_at->format('M d, Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                <i class="fa-solid fa-receipt text-3xl text-slate-300 mb-2"></i>
                                <p class="text-sm">No wallet transactions recorded yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
