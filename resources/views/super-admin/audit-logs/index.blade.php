@extends('layouts.admin')

@section('title', 'Security Audit Logs')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Security & Activity Audit Logs</h2>
        <p class="text-xs text-slate-500">Immutable logging of authentication events, tenant operations, and administrative overrides</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-5">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-4 py-3">Timestamp</th>
                        <th class="px-4 py-3">Event Action</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Organization</th>
                        <th class="px-4 py-3">IP Address</th>
                        <th class="px-4 py-3">Payload Summary</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                    @forelse($logs as $l)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3 text-slate-500 font-sans text-xs">{{ $l->created_at->format('Y-m-d H:i:s') }}</td>
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $l->action }}</td>
                            <td class="px-4 py-3 text-slate-700 font-sans">{{ $l->user?->name ?? 'Unauthenticated' }}</td>
                            <td class="px-4 py-3 text-slate-700 font-sans">{{ $l->organization?->name ?? 'Global Platform' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $l->ip_address }}</td>
                            <td class="px-4 py-3 text-slate-500 max-w-[200px] truncate">
                                {{ json_encode($l->new_values ?: $l->old_values) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400 font-sans">No audit events recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
