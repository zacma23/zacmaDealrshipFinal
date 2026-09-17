@extends('layouts.admin')

@section('title', 'Client Roster & Accounts')

@section('content')
<div class="space-y-6" x-data="{ addModal: false, importModal: false, balanceModal: false, activeCustomer: null }">
    <!-- Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Agency Client Roster</h2>
            <p class="text-xs text-slate-500 mt-0.5">Manage customer accounts registered directly through your agency or white-label child panel.</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="importModal = true" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs border border-slate-200 shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-file-import"></i> Bulk CSV Import
            </button>
            <button @click="addModal = true" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-user-plus"></i> New Customer
            </button>
        </div>
    </div>

    <!-- Customer Roster Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Client</th>
                        <th class="py-3 px-4">Contact</th>
                        <th class="py-3 px-4">Wallet Balance</th>
                        <th class="py-3 px-4">Total Orders</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Joined</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs uppercase">
                                        {{ substr($customer->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800 text-sm">{{ $customer->name }}</div>
                                        <div class="text-[11px] text-slate-400 font-mono">ID: #{{ $customer->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="text-slate-700 font-medium">{{ $customer->email }}</div>
                                <div class="text-[11px] text-slate-400">{{ $customer->phone ?? 'No phone' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 text-sm">
                                    ${{ number_format($customer->wallet->balance ?? 0, 2) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-700">
                                    {{ number_format($customer->smm_orders_count ?? 0) }} orders
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($customer->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Suspended
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-slate-400 text-[11px]">
                                {{ $customer->created_at->format('M d, Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Adjust Balance Button -->
                                    <button @click="activeCustomer = { id: {{ $customer->id }}, name: '{{ addslashes($customer->name) }}', balance: '{{ number_format($customer->wallet->balance ?? 0, 2) }}' }; balanceModal = true" 
                                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-semibold transition"
                                            title="Manual Balance Adjustment">
                                        <i class="fa-solid fa-coins mr-1 text-amber-500"></i> Adjust
                                    </button>

                                    <!-- Toggle Status Form -->
                                    <form method="POST" action="{{ route('reseller.customers.toggle', $customer) }}" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="px-2.5 py-1 rounded text-xs font-semibold transition {{ $customer->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-600' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700' }}"
                                                onclick="return confirm('Change status for {{ addslashes($customer->name) }}?')">
                                            {{ $customer->is_active ? 'Suspend' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-users-slash text-3xl mb-2 text-slate-300"></i>
                                <p>No customers registered under your organization yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    <!-- Create Customer Modal -->
    <div x-show="addModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
        <div @click.away="addModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 relative">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-bold text-slate-900 text-base">Add New Client Account</h3>
                <button @click="addModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form method="POST" action="{{ route('reseller.customers.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Full Name</label>
                    <input type="text" name="name" required placeholder="Jane Doe" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
                    <input type="email" name="email" required placeholder="client@example.com" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number (Optional)</label>
                    <input type="text" name="phone" placeholder="+1234567890" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Initial Wallet Credit ($)</label>
                    <input type="number" step="0.01" name="initial_balance" value="0.00" min="0" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-[11px] text-slate-400 mt-1">Automatically credited to the client's wallet upon registration.</p>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg text-xs">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-md">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk CSV Import Modal -->
    <div x-show="importModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
        <div @click.away="importModal = false" class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 relative">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-bold text-slate-900 text-base">Bulk CSV Client Import</h3>
                <button @click="importModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form method="POST" action="{{ route('reseller.customers.bulk-import') }}" class="space-y-4">
                @csrf
                <div class="p-3 bg-blue-50/70 border border-blue-200 rounded-xl text-xs text-blue-900 space-y-1">
                    <div class="font-bold"><i class="fa-solid fa-circle-info"></i> CSV Line Format:</div>
                    <code class="block bg-white p-2 rounded border border-blue-200 text-[11px] font-mono text-slate-800">
                        Full Name, Email Address, Phone (optional), Initial Balance (optional)
                    </code>
                    <p class="text-[11px] text-blue-700">Example:<br>John Smith, john@brand.com, +1234567890, 50.00</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Paste CSV Data</label>
                    <textarea name="csv_content" rows="6" required placeholder="John Doe, john@test.com, +1555123456, 25.00&#10;Jane Smith, jane@test.com,, 0" class="w-full text-xs font-mono p-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="importModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg text-xs">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-md">Run Bulk Import</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Adjust Balance Modal -->
    <div x-show="balanceModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
        <div @click.away="balanceModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 relative">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-bold text-slate-900 text-base">Adjust Client Balance</h3>
                <button @click="balanceModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form :action="activeCustomer ? '/reseller/customers/' + activeCustomer.id + '/adjust-balance' : '#'" method="POST" class="space-y-4">
                @csrf
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="text-xs text-slate-500">Selected Client:</div>
                    <div class="font-bold text-slate-800 text-sm" x-text="activeCustomer ? activeCustomer.name : ''"></div>
                    <div class="text-xs text-slate-500 mt-1">Current Balance: <span class="font-bold text-emerald-700" x-text="activeCustomer ? '$' + activeCustomer.balance : '$0.00'"></span></div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Adjustment Amount ($)</label>
                    <input type="number" step="0.01" name="amount" required placeholder="+10.00 to credit, -5.00 to debit" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-[11px] text-slate-400 mt-1">Enter a positive number to deposit/credit or negative to deduct.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Reason / Notes</label>
                    <input type="text" name="notes" required placeholder="Monthly agency retainer bonus" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="balanceModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg text-xs">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-md">Apply Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
