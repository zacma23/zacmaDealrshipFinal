@extends('layouts.app')

@section('title', 'Support Center & Tickets')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{ newTicketOpen: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold text-blue-600 mb-1">
                <a href="{{ route('customer.smm.dashboard') }}" class="hover:underline">Dashboard</a> &bull;
                <span>Support Center</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Customer Support Tickets</h1>
            <p class="text-sm text-slate-500">Need help with an order, refill, or payment? Our support engineers are available 24/7.</p>
        </div>
        <button @click="newTicketOpen = true"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-xl shadow transition flex items-center space-x-2">
            <i class="fa-solid fa-plus"></i>
            <span>Create New Ticket</span>
        </button>
    </div>

    <!-- New Ticket Modal -->
    <div x-show="newTicketOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.outside="newTicketOpen = false" class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                    <h3 class="font-extrabold text-slate-900 text-lg">Open Support Ticket</h3>
                </div>
                <button @click="newTicketOpen = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('customer.smm.tickets.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Subject</label>
                    <input type="text" name="subject" required placeholder="Brief summary of your question or issue"
                           class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-900 outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Category</label>
                        <select name="category" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-900 outline-none">
                            <option value="order">Order Inquiry</option>
                            <option value="payment">Payment / Deposit</option>
                            <option value="service">Service Request</option>
                            <option value="bug">Technical Bug</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Related Order (Optional)</label>
                        <select name="smm_order_id" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-900 outline-none">
                            <option value="">None</option>
                            @foreach($recentOrders as $ro)
                                <option value="{{ $ro->id }}">#{{ $ro->order_number }} ({{ $ro->service->name ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Detailed Message</label>
                    <textarea name="message" rows="5" required placeholder="Please describe what happened, order IDs, links, etc."
                              class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs font-medium text-slate-900 outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-sm py-3 rounded-xl shadow-lg transition flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Submit Ticket</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Tickets List & Thread View -->
    <div class="space-y-6">
        @forelse($tickets as $ticket)
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4" x-data="{ replyOpen: false }">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <span class="font-mono font-bold text-xs bg-slate-100 text-slate-800 px-2.5 py-1 rounded-lg">
                            #{{ $ticket->ticket_number }}
                        </span>
                        <h3 class="font-bold text-slate-900 text-base">{{ $ticket->subject }}</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        @php
                            $stBadge = match($ticket->status) {
                                'open' => 'bg-amber-100 text-amber-800',
                                'in_progress' => 'bg-blue-100 text-blue-800',
                                'answered' => 'bg-emerald-100 text-emerald-800',
                                'closed' => 'bg-slate-100 text-slate-600',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $stBadge }}">
                            {{ $ticket->status }}
                        </span>
                        <span class="text-[11px] text-slate-400">
                            {{ $ticket->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>

                <!-- Messages Thread -->
                <div class="space-y-3 pt-2">
                    @foreach($ticket->messages as $msg)
                        <div class="p-4 rounded-xl text-xs space-y-1 {{ $msg->is_staff ? 'bg-blue-50/80 border border-blue-100' : 'bg-slate-50 border border-slate-100' }}">
                            <div class="flex items-center justify-between font-semibold">
                                <span class="{{ $msg->is_staff ? 'text-blue-800' : 'text-slate-800' }}">
                                    {{ $msg->is_staff ? 'Customer Support Specialist' : Auth::user()->name }}
                                </span>
                                <span class="text-[10px] text-slate-400">{{ $msg->created_at->format('M d, H:i') }}</span>
                            </div>
                            <p class="text-slate-700 whitespace-pre-line leading-relaxed">{{ $msg->message }}</p>
                        </div>
                    @endforeach
                </div>

                <!-- Reply Action -->
                @if($ticket->status !== 'closed')
                    <div class="pt-2">
                        <button @click="replyOpen = !replyOpen" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            <i class="fa-solid fa-reply"></i>
                            <span x-text="replyOpen ? 'Cancel' : 'Send Reply'"></span>
                        </button>

                        <form x-show="replyOpen" x-cloak action="{{ route('customer.smm.tickets.reply', $ticket) }}" method="POST" class="mt-3 space-y-3">
                            @csrf
                            <textarea name="message" rows="3" required placeholder="Type your response..."
                                      class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs font-medium text-slate-900 outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-4 py-2 rounded-xl transition">
                                Post Reply
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-400">
                <i class="fa-solid fa-headset text-4xl text-slate-300 mb-3"></i>
                <p class="text-sm font-medium">No support tickets yet. Have a question or concern?</p>
                <button @click="newTicketOpen = true" class="mt-3 inline-flex items-center px-4 py-2 bg-blue-600 text-white font-bold text-xs rounded-xl hover:bg-blue-700">
                    Open Your First Ticket
                </button>
            </div>
        @endforelse

        @if($tickets->hasPages())
            <div class="px-6 py-4">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
