@extends('layouts.admin')

@section('title', 'Platform Wallets & Liquidity')

@section('content')
<div class="space-y-6" x-data="{ adjustModal: false, activeWallet: null }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">User Wallets & Liquidity Ledger</h2>
            <p class="text-xs text-slate-500 mt-0.5">Prepaid customer and reseller balances with pessimistic row locking and immutable audit ledgers.</p>
        </div>
    </div>

    <!-- Financial Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total User Balance</span>
                <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg text-sm"><i class="fa-solid fa-wallet"></i></span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900">${{ number_format($totalBalance, 2) }}</div>
                <div class="text-xs text-slate-500 mt-1">Total active customer & reseller liquidity</div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Lifetime Deposits</span>
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg text-sm"><i class="fa-solid fa-money-bill-transfer"></i></span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900">${{ number_format($totalDeposited, 2) }}</div>
                <div class="text-xs text-slate-500 mt-1">Deposited via gateways & manual credits</div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Lifetime Order Spend</span>
                <span class="p-2 bg-purple-50 text-purple-600 rounded-lg text-sm"><i class="fa-solid fa-receipt"></i></span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-slate-900">${{ number_format($totalSpent, 2) }}</div>
                <div class="text-xs text-slate-500 mt-1">Executed against services</div>
            </div>
        </div>
    </div>

    <!-- Wallets Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">User</th>
                        <th class="py-3 px-4">Role & Tenant</th>
                        <th class="py-3 px-4">Currency</th>
                        <th class="py-3 px-4">Available Balance</th>
                        <th class="py-3 px-4">Reserved</th>
                        <th class="py-3 px-4">Deposited</th>
                        <th class="py-3 px-4">Spent</th>
                        <th class="py-3 px-4 text-right">Adjustment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($wallets as $wallet)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-800 text-sm">{{ $wallet->user->name ?? 'Deleted User' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $wallet->user->email ?? '' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 capitalize">
                                    {{ $wallet->user->role ?? 'N/A' }}
                                </span>
                                <div class="text-[10px] text-slate-400 mt-0.5">{{ $wallet->user->organization->name ?? 'Direct Platform' }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-600 uppercase">
                                {{ $wallet->currency }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-extrabold text-slate-900 text-sm">
                                ${{ number_format($wallet->balance, 2) }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-500">
                                ${{ number_format($wallet->reserved_balance, 2) }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-emerald-700">
                                ${{ number_format($wallet->total_deposited, 2) }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-purple-700">
                                ${{ number_format($wallet->total_spent, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <button @click="activeWallet = { id: {{ $wallet->id }}, name: '{{ addslashes($wallet->user->name ?? 'User') }}', balance: '{{ number_format($wallet->balance, 2) }}' }; adjustModal = true" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-semibold transition">
                                    <i class="fa-solid fa-coins mr-1 text-amber-500"></i> Adjust
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                No wallets recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($wallets->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $wallets->links() }}
            </div>
        @endif
    </div>

    <!-- Balance Adjustment Modal -->
    <div x-show="adjustModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
        <div @click.away="adjustModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 relative">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-bold text-slate-900 text-base">Super Admin Wallet Adjustment</h3>
                <button @click="adjustModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form :action="activeWallet ? '/super-admin/smm/wallets/' + activeWallet.id + '/adjust' : '#'" method="POST" class="space-y-4">
                @csrf
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="text-xs text-slate-500">Target User:</div>
                    <div class="font-bold text-slate-800 text-sm" x-text="activeWallet ? activeWallet.name : ''"></div>
                    <div class="text-xs text-slate-500 mt-1">Current Balance: <span class="font-bold text-emerald-700" x-text="activeWallet ? '$' + activeWallet.balance : '$0.00'"></span></div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Adjustment Amount ($)</label>
                    <input type="number" step="0.01" name="amount" required placeholder="+100.00 to credit, -50.00 to debit" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Audit Reason / Admin Note</label>
                    <input type="text" name="notes" required placeholder="Direct wire deposit reconciliation" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="adjustModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg text-xs">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-md">Submit Ledger Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
