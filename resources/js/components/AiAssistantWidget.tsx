import React, { useState, useEffect, useRef } from 'react';
import { User } from '../types/marketplace';
import { api } from '../services/api';
import { 
    Sparkles, X, Send, Bot, Trash2, ChevronDown, 
    Maximize2, Minimize2, Copy, Check, HelpCircle, 
    ShieldCheck, ArrowRight 
} from 'lucide-react';

interface AiAssistantWidgetProps {
    user: User | null;
    activeTab: string;
    onNavigateTab?: (tab: string) => void;
}

interface ChatMessage {
    role: 'user' | 'assistant';
    content: string;
    time?: string;
}

export const AiAssistantWidget: React.FC<AiAssistantWidgetProps> = ({
    user,
    activeTab,
    onNavigateTab,
}) => {
    const [isOpen, setIsOpen] = useState(false);
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [suggestions, setSuggestions] = useState<string[]>([]);
    const [copiedIndex, setCopiedIndex] = useState<number | null>(null);
    const messagesEndRef = useRef<HTMLDivElement | null>(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        if (isOpen) {
            scrollToBottom();
        }
    }, [messages, isOpen]);

    // Load initial history and suggestions
    useEffect(() => {
        api.getAiHistory(activeTab)
            .then(res => {
                if (res?.history && res.history.length > 0) {
                    setMessages(res.history);
                } else {
                    // Default friendly greeting
                    setMessages([
                        {
                            role: 'assistant',
                            content: `Hello ${user ? user.name : 'there'}! 👋 I am the **Zacma AI Knowledge Assistant**.\n\nI can help you with anything regarding the Zacma platform:\n- **One Account Rule**: How you can buy and sell from the same account.\n- **Subscriptions & Quotas**: Basic (20 free), Premium (50), Pro (100).\n- **Ethiopian Payments**: Chapa, Telebirr, CBE Birr, eBirr, SantimPay.\n- **CRM & Buyer Needs**: How inbound leads work and how to post requirements.\n\nHow can I help you today?`,
                            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                        }
                    ]);
                }
                if (res?.suggestions) {
                    setSuggestions(res.suggestions);
                }
            })
            .catch(() => {
                setSuggestions([
                    "Can I sell without a separate account?",
                    "How does the listing quota work?",
                    "What payment gateways are supported in Ethiopia?",
                ]);
            });
    }, [activeTab, user]);

    const handleSendMessage = async (textToSend?: string) => {
        const query = (textToSend || input).trim();
        if (!query || loading) return;

        const userMsg: ChatMessage = {
            role: 'user',
            content: query,
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        };

        setMessages(prev => [...prev, userMsg]);
        setInput('');
        setLoading(true);

        try {
            const res = await api.chatWithAi(query, activeTab);
            const assistantMsg: ChatMessage = {
                role: 'assistant',
                content: res.reply || "I'm here to assist with any questions about Zacma Marketplace & CRM.",
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            };
            setMessages(prev => [...prev, assistantMsg]);
            if (res.suggestions) {
                setSuggestions(res.suggestions);
            }
        } catch (err) {
            console.error('AI chat failed', err);
            setMessages(prev => [
                ...prev,
                {
                    role: 'assistant',
                    content: "I'm temporarily having trouble connecting to the knowledge service. Please verify your connection or try again in a moment.",
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                }
            ]);
        } finally {
            setLoading(false);
        }
    };

    const handleClearChat = async () => {
        try {
            await api.clearAiHistory();
            setMessages([
                {
                    role: 'assistant',
                    content: "Conversation cleared. How can I help you explore Zacma today?",
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                }
            ]);
        } catch (err) {
            console.error('Failed to clear history', err);
        }
    };

    const copyToClipboard = (text: string, index: number) => {
        navigator.clipboard.writeText(text);
        setCopiedIndex(index);
        setTimeout(() => setCopiedIndex(null), 2000);
    };

    // Format simple markdown (bold, bullets, headings)
    const renderMarkdown = (content: string) => {
        return content.split('\n').map((line, idx) => {
            if (line.startsWith('### ')) {
                return <h4 key={idx} className="font-extrabold text-slate-900 text-xs mt-2 mb-1">{line.replace('### ', '')}</h4>;
            }
            if (line.startsWith('## ')) {
                return <h3 key={idx} className="font-black text-slate-900 text-sm mt-2 mb-1">{line.replace('## ', '')}</h3>;
            }
            if (line.startsWith('- ') || line.startsWith('* ')) {
                const bulletText = line.substring(2);
                return (
                    <div key={idx} className="flex items-start gap-1.5 ml-1 my-0.5 text-xs">
                        <span className="text-emerald-500 font-bold">•</span>
                        <span dangerouslySetInnerHTML={{ __html: formatInline(bulletText) }} />
                    </div>
                );
            }
            if (line.trim() === '') {
                return <div key={idx} className="h-1.5" />;
            }
            return (
                <p key={idx} className="text-xs leading-relaxed my-0.5" dangerouslySetInnerHTML={{ __html: formatInline(line) }} />
            );
        });
    };

    const formatInline = (str: string) => {
        // Bold: **text**
        let s = str.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900">$1</strong>');
        // Code: `code`
        s = s.replace(/`(.*?)`/g, '<code class="bg-slate-100 text-emerald-800 px-1 py-0.5 rounded font-mono text-[11px]">$1</code>');
        return s;
    };

    return (
        <>
            {/* Floating Trigger Button */}
            {!isOpen && (
                <button
                    onClick={() => setIsOpen(true)}
                    className="fixed bottom-6 right-6 z-40 group flex items-center gap-2.5 bg-gradient-to-r from-emerald-600 via-teal-600 to-slate-900 text-white p-3.5 sm:px-4 sm:py-3.5 rounded-2xl shadow-xl shadow-emerald-900/20 hover:scale-105 active:scale-95 transition-all duration-200 border border-emerald-400/30"
                    aria-label="Open AI Assistant"
                >
                    <div className="relative">
                        <Sparkles className="w-5 h-5 animate-pulse text-amber-300" />
                        <span className="absolute -top-1 -right-1 w-2.5 h-2.5 bg-emerald-400 rounded-full ring-2 ring-white"></span>
                    </div>
                    <div className="hidden sm:block text-left">
                        <div className="text-xs font-black tracking-tight flex items-center gap-1">
                            <span>Zacma AI Copilot</span>
                        </div>
                        <div className="text-[10px] text-emerald-200 font-medium">Knowledge & Guides</div>
                    </div>
                </button>
            )}

            {/* Expandable Chat Drawer / Window */}
            {isOpen && (
                <div className="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 w-[calc(100vw-2rem)] sm:w-[420px] max-h-[640px] h-[85vh] bg-white rounded-3xl shadow-2xl border border-slate-200/90 flex flex-col overflow-hidden animate-in fade-in slide-in-from-bottom-5 duration-200">
                    {/* Header */}
                    <div className="bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-950 p-4 text-white flex items-center justify-between border-b border-white/10 shrink-0">
                        <div className="flex items-center gap-2.5">
                            <div className="w-8 h-8 rounded-xl bg-emerald-500/20 border border-emerald-400/30 flex items-center justify-center text-emerald-300">
                                <Bot className="w-4 h-4" />
                            </div>
                            <div>
                                <div className="flex items-center gap-1.5">
                                    <h3 className="text-xs font-black tracking-tight">Zacma AI Assistant</h3>
                                    <span className="bg-emerald-500/20 text-emerald-300 text-[9px] font-bold px-1.5 py-0.2 rounded-full border border-emerald-400/30">
                                        Knowledge Mode
                                    </span>
                                </div>
                                <p className="text-[10px] text-slate-300">
                                    {user ? `${user.name} (${user.role})` : 'Guest Visitor'} • View: {activeTab}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-1">
                            <button
                                onClick={handleClearChat}
                                title="Clear conversation"
                                className="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition"
                            >
                                <Trash2 className="w-3.5 h-3.5" />
                            </button>
                            <button
                                onClick={() => setIsOpen(false)}
                                title="Close Assistant"
                                className="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    {/* Safety Banner */}
                    <div className="bg-slate-50 border-b border-slate-100 px-3 py-1.5 flex items-center justify-between text-[10px] text-slate-500 shrink-0">
                        <span className="flex items-center gap-1">
                            <ShieldCheck className="w-3 h-3 text-emerald-600" />
                            <span>Explains features, listing rules & pricing (ETB)</span>
                        </span>
                        <span className="font-semibold text-slate-400">Offline & Online</span>
                    </div>

                    {/* Messages Container */}
                    <div className="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50/40">
                        {messages.map((msg, index) => {
                            const isUser = msg.role === 'user';
                            return (
                                <div
                                    key={index}
                                    className={`flex flex-col ${isUser ? 'items-end' : 'items-start'} group`}
                                >
                                    <div
                                        className={`max-w-[88%] p-3.5 rounded-2xl text-xs relative ${
                                            isUser
                                                ? 'bg-slate-900 text-white rounded-br-xs shadow-xs'
                                                : 'bg-white text-slate-800 rounded-bl-xs border border-slate-200/80 shadow-xs'
                                        }`}
                                    >
                                        {isUser ? (
                                            <p className="leading-relaxed whitespace-pre-line">{msg.content}</p>
                                        ) : (
                                            <div>
                                                {renderMarkdown(msg.content)}
                                                <div className="mt-2 pt-1 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400">
                                                    <span>Verified System Knowledge</span>
                                                    <button
                                                        onClick={() => copyToClipboard(msg.content, index)}
                                                        className="hover:text-slate-600 flex items-center gap-0.5 transition"
                                                    >
                                                        {copiedIndex === index ? (
                                                            <>
                                                                <Check className="w-2.5 h-2.5 text-emerald-600" />
                                                                <span className="text-emerald-600">Copied</span>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <Copy className="w-2.5 h-2.5" />
                                                                <span>Copy</span>
                                                            </>
                                                        )}
                                                    </button>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                    {msg.time && (
                                        <span className="text-[9px] text-slate-400 mt-1 px-1">
                                            {msg.time}
                                        </span>
                                    )}
                                </div>
                            );
                        })}

                        {loading && (
                            <div className="flex items-center gap-2 p-3 bg-white rounded-2xl border border-slate-200/80 w-fit text-xs text-slate-500 shadow-xs">
                                <div className="w-2 h-2 rounded-full bg-emerald-500 animate-bounce"></div>
                                <div className="w-2 h-2 rounded-full bg-emerald-500 animate-bounce [animation-delay:-.3s]"></div>
                                <div className="w-2 h-2 rounded-full bg-emerald-500 animate-bounce [animation-delay:-.5s]"></div>
                                <span className="text-[11px] text-slate-400 ml-1">Consulting knowledge base...</span>
                            </div>
                        )}
                        <div ref={messagesEndRef} />
                    </div>

                    {/* Quick Suggestion Chips */}
                    {suggestions.length > 0 && !loading && (
                        <div className="p-2 bg-white border-t border-slate-100 flex gap-1.5 overflow-x-auto shrink-0 scrollbar-none">
                            {suggestions.map((sug, idx) => (
                                <button
                                    key={idx}
                                    onClick={() => handleSendMessage(sug)}
                                    className="whitespace-nowrap bg-slate-100 hover:bg-emerald-50 hover:text-emerald-800 text-slate-700 text-[10px] font-semibold px-2.5 py-1.5 rounded-xl border border-slate-200/60 transition shrink-0 flex items-center gap-1"
                                >
                                    <span>{sug}</span>
                                    <ArrowRight className="w-2.5 h-2.5 opacity-60" />
                                </button>
                            ))}
                        </div>
                    )}

                    {/* Input Field */}
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            handleSendMessage();
                        }}
                        className="p-3 bg-white border-t border-slate-100 flex items-center gap-2 shrink-0"
                    >
                        <input
                            type="text"
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            placeholder="Ask about listings, quotas, Chapa/Telebirr, CRM..."
                            disabled={loading}
                            className="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none disabled:opacity-50"
                        />
                        <button
                            type="submit"
                            disabled={loading || !input.trim()}
                            className="bg-emerald-600 hover:bg-emerald-700 text-white p-2 rounded-xl transition shadow-xs disabled:opacity-50 disabled:cursor-not-allowed"
                            aria-label="Send message"
                        >
                            <Send className="w-3.5 h-3.5" />
                        </button>
                    </form>
                </div>
            )}
        </>
    );
};

