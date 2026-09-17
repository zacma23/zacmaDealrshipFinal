@php
    $user = Auth::user();
    $role = $user ? $user->role : 'GUEST';
    $org = $user?->organization;
    $isBranded = $org && !empty($org->brand_name);
    $agencyHandle = $org ? ($org->subdomain ?: $org->slug) : 'agency';

    $roleTitle = match($role) {
        'SUPER_ADMIN' => 'Zacma SMM Director AI',
        'ORGANIZATION_ADMIN', 'MANAGER', 'SALES_AGENT', 'STAFF', 'RESELLER', 'SELLER' => $isBranded ? "@{$agencyHandle} Agency Copilot" : 'SMM Reseller Copilot',
        'CUSTOMER' => 'Zacma SMM Growth Assistant',
        default => 'Zacma SMM Guide'
    };

    $quickPrompts = match($role) {
        'SUPER_ADMIN' => [
            'Summarize platform orders & revenue',
            'Check provider latency & API balances',
            'How do I add a new SMM service?',
            'Review active white-label child panels',
        ],
        'ORGANIZATION_ADMIN', 'MANAGER', 'SALES_AGENT', 'STAFF', 'RESELLER', 'SELLER' => [
            'Which SMM services have highest margins?',
            'How do I connect my website via SMM API?',
            'How do I set custom markups for child panels?',
            'Check my current reseller wallet balance',
        ],
        'CUSTOMER' => [
            'What is the best package for Instagram followers?',
            'How do I track my active SMM order?',
            'How do I request a refill or cancel an order?',
            'How do I deposit funds into my wallet?',
        ],
        default => [
            'What platforms and services are available?',
            'How fast is order delivery completed?',
            'How do I register as a wholesale reseller?',
            'What payment methods are supported?',
        ]
    };
@endphp

<div x-data="aiChatWidget()" x-init="initChat()" class="fixed bottom-6 right-6 z-50 font-sans">
    <!-- Floating Launcher Bubble -->
    <button 
        @click="toggleChat()" 
        class="flex items-center gap-2.5 px-4 py-3 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white rounded-full shadow-2xl hover:shadow-emerald-500/25 transition-all duration-300 transform hover:scale-105 group border border-emerald-400/30"
        :class="{ 'ring-4 ring-emerald-500/20': isOpen }"
        title="Open AI Assistant"
    >
        <span class="relative flex h-3.5 w-3.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-300 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-200"></span>
        </span>
        <svg class="w-5 h-5 text-white animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        <span class="text-xs font-bold tracking-wide uppercase pr-1">AI Assistant</span>
        <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-semibold">{{ $role === 'GUEST' ? 'Guest' : ($role === 'SUPER_ADMIN' ? 'Admin' : ($role === 'CUSTOMER' ? 'Shopper' : 'CRM')) }}</span>
    </button>

    <!-- Expandable Chat Panel -->
    <div 
        x-show="isOpen" 
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 translate-y-8 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 scale-95"
        x-cloak
        class="fixed bottom-24 right-6 w-96 max-w-[calc(100vw-2rem)] h-[560px] bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl flex flex-col overflow-hidden text-slate-100 z-50 backdrop-blur-xl"
    >
        <!-- Panel Header -->
        <div class="px-5 py-4 bg-gradient-to-r from-slate-800 to-slate-900 border-b border-slate-700/80 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">{{ $roleTitle }}</h3>
                    <div class="flex items-center gap-1.5 text-[11px] text-emerald-400 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Gemini 1.5 Flash Active</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <button @click="clearChat()" title="Clear conversation" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                <button @click="toggleChat()" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- Quick Prompts Pill Carousel -->
        <div class="px-4 py-2.5 bg-slate-950/60 border-b border-slate-800 overflow-x-auto scrollbar-none flex gap-1.5 whitespace-nowrap">
            @foreach($quickPrompts as $prompt)
                <button 
                    @click="sendPrompt('{{ addslashes($prompt) }}')" 
                    class="text-[11px] font-medium bg-slate-800/80 hover:bg-emerald-900/50 hover:text-emerald-300 hover:border-emerald-500/40 text-slate-300 px-3 py-1 rounded-full border border-slate-700/60 transition flex-shrink-0"
                >
                    {{ $prompt }}
                </button>
            @endforeach
        </div>

        <!-- Message History Feed -->
        <div id="aiChatFeed" class="flex-1 p-4 overflow-y-auto space-y-3.5 text-xs">
            <!-- Welcome Greeting -->
            <div class="flex items-start gap-2.5">
                <div class="w-6 h-6 rounded-full bg-emerald-600/30 border border-emerald-500/40 flex items-center justify-center text-emerald-400 flex-shrink-0 mt-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl rounded-tl-none p-3 max-w-[85%] text-slate-200 leading-relaxed shadow-sm">
                    <p class="font-semibold text-emerald-400 mb-1">Hello! How can I assist you today?</p>
                    <p>I am your role-tailored Zacma AI assistant. Ask me anything about listings, customer leads, pricing, or marketplace operations.</p>
                </div>
            </div>

            <!-- Dynamic Messages -->
            <template x-for="(msg, index) in messages" :key="index">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex items-start gap-2.5'">
                    <!-- Assistant Avatar -->
                    <template x-if="msg.role === 'assistant'">
                        <div class="w-6 h-6 rounded-full bg-emerald-600/30 border border-emerald-500/40 flex items-center justify-center text-emerald-400 flex-shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                    </template>

                    <div 
                        :class="msg.role === 'user' 
                            ? 'bg-emerald-600 text-white rounded-2xl rounded-tr-none px-3.5 py-2.5 max-w-[80%] shadow-sm' 
                            : 'bg-slate-800/90 border border-slate-700/80 rounded-2xl rounded-tl-none p-3 max-w-[85%] text-slate-200 leading-relaxed shadow-sm'"
                    >
                        <div class="whitespace-pre-line" x-text="msg.content"></div>
                        <div class="text-[10px] mt-1 text-right opacity-60" x-text="msg.time || ''"></div>
                    </div>
                </div>
            </template>

            <!-- Typing Indicator -->
            <div x-show="isLoading" class="flex items-center gap-2 text-slate-400 text-[11px] pt-1">
                <div class="flex gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-bounce"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-bounce [animation-delay:0.4s]"></span>
                </div>
                <span>Zacma AI is thinking...</span>
            </div>
        </div>

        <!-- Chat Input Bar -->
        <div class="p-3 bg-slate-950 border-t border-slate-800">
            <form @submit.prevent="submitMessage()" class="flex items-center gap-2">
                <input 
                    type="text" 
                    x-model="inputMessage" 
                    :disabled="isLoading"
                    placeholder="Type your question or request..."
                    class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition disabled:opacity-50"
                />
                <button 
                    type="submit" 
                    :disabled="isLoading || !inputMessage.trim()"
                    class="p-2.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 disabled:hover:bg-emerald-600 text-white rounded-xl transition flex-shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function aiChatWidget() {
    return {
        isOpen: false,
        isLoading: false,
        inputMessage: '',
        messages: [],

        initChat() {
            fetch('{{ route('ai.chat.history') }}')
                .then(r => r.json())
                .then(data => {
                    if (data.history && data.history.length > 0) {
                        this.messages = data.history;
                    }
                })
                .catch(() => {});
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        sendPrompt(promptText) {
            this.inputMessage = promptText;
            this.submitMessage();
        },

        submitMessage() {
            const text = this.inputMessage.trim();
            if (!text || this.isLoading) return;

            const now = new Date();
            const timeStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

            this.messages.push({
                role: 'user',
                content: text,
                time: timeStr
            });

            this.inputMessage = '';
            this.isLoading = true;
            this.$nextTick(() => this.scrollToBottom());

            fetch('{{ route('ai.chat') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    message: text,
                    page_context: window.location.pathname
                })
            })
            .then(r => r.json())
            .then(data => {
                this.isLoading = false;
                if (data.reply) {
                    this.messages.push({
                        role: 'assistant',
                        content: data.reply,
                        time: timeStr
                    });
                }
                this.$nextTick(() => this.scrollToBottom());
            })
            .catch(err => {
                this.isLoading = false;
                this.messages.push({
                    role: 'assistant',
                    content: "Sorry, I encountered an issue processing your message. Please try again.",
                    time: timeStr
                });
                this.$nextTick(() => this.scrollToBottom());
            });
        },

        clearChat() {
            if (!confirm('Clear AI conversation history?')) return;
            fetch('{{ route('ai.chat.clear') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).then(() => {
                this.messages = [];
            });
        },

        scrollToBottom() {
            const feed = document.getElementById('aiChatFeed');
            if (feed) {
                feed.scrollTop = feed.scrollHeight;
            }
        }
    };
}
</script>
