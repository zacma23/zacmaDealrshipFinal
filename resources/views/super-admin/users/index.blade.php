@extends('layouts.admin')

@section('title', 'Users & Access Control')

@section('content')
<div class="space-y-6">
    <!-- Header & Breadcrumbs -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('super-admin.dashboard') }}" class="hover:text-blue-600">Super Admin</a>
                <i class="fa-solid fa-chevron-right text-[9px]"></i>
                <span class="text-slate-700 font-semibold">Accounts</span>
                <i class="fa-solid fa-chevron-right text-[9px]"></i>
                <span class="text-blue-600 font-semibold">All Users & Roles</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-users-gear text-blue-600"></i>
                <span>Platform Users & Access Control</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage user credentials, organization allocations, status toggles, and direct View As impersonation.</p>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('super-admin.users.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by user name, email, phone..." class="w-full pl-9 pr-4 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            <div class="sm:w-56">
                <select name="role" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-slate-700">
                    <option value="">All Platform Roles</option>
                    <option value="SUPER_ADMIN" {{ request('role') === 'SUPER_ADMIN' ? 'selected' : '' }}>SUPER_ADMIN</option>
                    <option value="ORGANIZATION_ADMIN" {{ request('role') === 'ORGANIZATION_ADMIN' ? 'selected' : '' }}>ORGANIZATION_ADMIN (Reseller/Agency)</option>
                    <option value="MANAGER" {{ request('role') === 'MANAGER' ? 'selected' : '' }}>MANAGER</option>
                    <option value="SALES_AGENT" {{ request('role') === 'SALES_AGENT' ? 'selected' : '' }}>SALES_AGENT</option>
                    <option value="STAFF" {{ request('role') === 'STAFF' ? 'selected' : '' }}>STAFF</option>
                    <option value="SELLER" {{ request('role') === 'SELLER' ? 'selected' : '' }}>RESELLER / SELLER</option>
                    <option value="CUSTOMER" {{ request('role') === 'CUSTOMER' ? 'selected' : '' }}>CUSTOMER</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center space-x-1.5">
                <i class="fa-solid fa-filter"></i>
                <span>Filter</span>
            </button>
            @if(request()->hasAny(['q', 'role']))
                <a href="{{ route('super-admin.users.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold flex items-center justify-center">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-4 py-3.5">User Identity</th>
                        <th class="px-4 py-3.5">Role</th>
                        <th class="px-4 py-3.5">Organization / Child Panel</th>
                        <th class="px-4 py-3.5">Phone</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5">Joined</th>
                        <th class="px-4 py-3.5 text-right">Actions & Impersonation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $u)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center space-x-3">
                                    <img src="{{ $u->getAvatarUrl() }}" alt="{{ $u->name }}" class="w-9 h-9 rounded-full object-cover border border-slate-200 flex-shrink-0">
                                    <div>
                                        <div class="font-bold text-slate-900 text-xs">{{ $u->name }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $u->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                @php
                                    $roleColors = [
                                        'SUPER_ADMIN' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        'ORGANIZATION_ADMIN' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'MANAGER' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                        'SALES_AGENT' => 'bg-amber-100 text-amber-800 border-amber-200',
                                        'SELLER' => 'bg-teal-100 text-teal-800 border-teal-200',
                                        'CUSTOMER' => 'bg-slate-100 text-slate-700 border-slate-200',
                                        'STAFF' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $roleColors[$u->role] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $u->role }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                @if($u->organization)
                                    <span class="font-semibold text-slate-800">{{ $u->organization->name }}</span>
                                @else
                                    <span class="text-slate-400 italic">No Tenant (Global)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-slate-500">
                                {{ $u->phone ?? '—' }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $u->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ $u->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-slate-400 text-[11px]">
                                {{ $u->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end space-x-1.5">
                                    @if($u->id !== auth()->id())
                                        <!-- View As / Impersonate Button -->
                                        <form method="POST" action="{{ route('super-admin.impersonate', $u->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-slate-950 rounded-lg text-xs font-black transition flex items-center space-x-1 shadow-sm" title="Impersonate & View platform as {{ $u->name }}">
                                                <i class="fa-solid fa-user-secret"></i>
                                                <span>View As</span>
                                            </button>
                                        </form>

                                        <!-- Toggle Active/Inactive -->
                                        <form method="POST" action="{{ route('super-admin.users.toggle', $u->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 {{ $u->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-600' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600' }} rounded-lg text-xs font-bold transition" title="{{ $u->is_active ? 'Deactivate Account' : 'Activate Account' }}">
                                                <i class="fa-solid {{ $u->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[10px] text-purple-600 font-bold uppercase px-2 py-0.5 bg-purple-50 rounded">Current Session</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                <i class="fa-solid fa-users text-3xl mb-2 text-slate-300 block"></i>
                                No users found matching the given filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection