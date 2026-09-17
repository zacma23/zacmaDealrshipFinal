@extends('layouts.admin')

@section('title', 'SMM Support Desk & Tickets')

@section('content')
<div class="space-y-6" x-data="{ replyModal: false, activeTicket: null }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">SMM Support Ticket Desk</h2>
            <p class="text-xs text-slate-500 mt-0.5">Resolve order inquiries, speed issues, refill requests, and customer questions.</p>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Ticket #</th>
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4">Subject & Details</th>
                        <th class="py-3 px-4">Order Linked</th>
                        <th class="py-3 px-4">Priority</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Last Reply</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tickets as $ticket)
                        @php
                            $statusBadges = [
                                'open' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'in_progress' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'answered' => 'bg-purple-50 text-purple-700 border-purple-200',
                                'closed' => 'bg-slate-100 text-slate-600 border-slate-200',
                            ];
                            $badgeClass = $statusBadges[$ticket->status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-700">#{{ $ticket->id }}</td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-800 text-sm">{{ $ticket->user->name ?? 'User' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $ticket->user->email ?? '' }}</div>
                            </td>
                            <td class="py-3.5 px-4 max-w-sm">
                                <div class="font-bold text-slate-800">{{ $ticket->subject }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ $ticket->messages->count() }} message(s)</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                @if($ticket->smm_order_id)
                                    <span class="text-blue-600 font-bold">#{{ $ticket->smm_order_id }}</span>
                                @else
                                    <span class="text-slate-400">None</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $ticket->priority === 'urgent' ? 'bg-rose-100 text-rose-700' : ($ticket->priority === 'high' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                                    {{ $ticket->priority }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }} capitalize">
                                    {{ str_replace('_', ' ', $ticket->status) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-400 text-[11px]">
                                {{ $ticket->last_reply_at ? $ticket->last_reply_at->diffForHumans() : $ticket->created_at->diffForHumans() }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <button @click="activeTicket = { 
                                    id: {{ $ticket->id }}, 
                                    subject: '{{ addslashes($ticket->subject) }}', 
                                    customer: '{{ addslashes($ticket->user->name ?? 'User') }}',
                                    messages: {{ json_encode($ticket->messages->map(fn($m) => ['sender' => $m->user->name ?? 'Staff', 'is_staff' => $m->is_staff, 'message' => $m->message, 'time' => $m->created_at->diffForHumans()])) }}
                                }; replyModal = true" class="px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded text-xs font-semibold transition flex items-center gap-1.5 ml-auto">
                                    <i class="fa-solid fa-reply"></i> Reply
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                No support tickets submitted.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>

    <!-- Reply Modal -->
    <div x-show="replyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
        <div @click.away="replyModal = false" class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 relative max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3 flex-shrink-0">
                <div>
                    <h3 class="font-bold text-slate-900 text-base" x-text="activeTicket ? 'Ticket #' + activeTicket.id + ': ' + activeTicket.subject : ''"></h3>
                    <p class="text-xs text-slate-400" x-text="activeTicket ? 'Client: ' + activeTicket.customer : ''"></p>
                </div>
                <button @click="replyModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <!-- Messages Thread -->
            <div class="flex-1 overflow-y-auto space-y-3 p-3 bg-slate-50 rounded-xl border border-slate-200 mb-4 max-h-64">
                <template x-for="(msg, index) in (activeTicket ? activeTicket.messages : [])" :key="index">
                    <div :class="msg.is_staff ? 'text-right' : 'text-left'">
                        <div class="inline-block max-w-lg p-3 rounded-xl text-xs" :class="msg.is_staff ? 'bg-blue-600 text-white' : 'bg-white text-slate-800 border border-slate-200 shadow-sm'">
                            <div class="font-bold text-[10px] mb-1" :class="msg.is_staff ? 'text-blue-100' : 'text-slate-500'" x-text="msg.sender + ' • ' + msg.time"></div>
                            <div class="whitespace-pre-line text-xs" x-text="msg.message"></div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Reply Form -->
            <form :action="activeTicket ? '/super-admin/smm/tickets/' + activeTicket.id + '/reply' : '#'" method="POST" class="space-y-3 flex-shrink-0">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Staff Reply</label>
                    <textarea name="message" rows="3" required placeholder="Write your response..." class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-4">
                        <select name="status" class="text-xs p-2 bg-slate-50 border border-slate-200 rounded-lg">
                            <option value="answered">Mark Answered</option>
                            <option value="in_progress">Mark In Progress</option>
                            <option value="closed">Close Ticket</option>
                        </select>
                        <label class="flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer">
                            <input type="checkbox" name="is_internal_note" value="1" class="w-4 h-4 text-blue-600 rounded">
                            <span>Internal Staff Note Only</span>
                        </label>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" @click="replyModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg text-xs">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-md">Send Reply</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
