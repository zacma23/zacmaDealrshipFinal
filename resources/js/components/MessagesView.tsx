import React, { useState, useEffect } from 'react';
import { User } from '../types/marketplace';
import { api } from '../services/api';
import { 
    MessageSquare, Send, User as UserIcon, Clock, 
    CheckCheck, ExternalLink, Sparkles, AlertCircle 
} from 'lucide-react';

interface MessagesViewProps {
    currentUser: User | null;
    initialRecipientId?: number | null;
    initialListingId?: number | null;
    onViewListing?: (slug: string) => void;
}

export const MessagesView: React.FC<MessagesViewProps> = ({
    currentUser,
    initialRecipientId,
    initialListingId,
    onViewListing,
}) => {
    const [conversations, setConversations] = useState<any[]>([]);
    const [selectedUser, setSelectedUser] = useState<any | null>(null);
    const [messages, setMessages] = useState<any[]>([]);
    const [newMessage, setNewMessage] = useState('');
    const [loading, setLoading] = useState(true);
    const [sending, setSending] = useState(false);

    const loadConversations = async () => {
        try {
            const data = await api.getConversations();
            setConversations(data || []);
            if (data && data.length > 0 && !selectedUser && !initialRecipientId) {
                setSelectedUser(data[0].user);
            }
        } catch (err) {
            console.error('Failed to load conversations', err);
        } finally {
            setLoading(false);
        }
    };

    const loadThread = async (userId: number) => {
        try {
            const data = await api.getMessageThread(userId);
            setMessages(data || []);
        } catch (err) {
            console.error('Failed to load thread', err);
        }
    };

    useEffect(() => {
        loadConversations();
    }, []);

    useEffect(() => {
        if (selectedUser) {
            loadThread(selectedUser.id);
        }
    }, [selectedUser]);

    useEffect(() => {
        if (initialRecipientId) {
            setSelectedUser({ id: initialRecipientId, name: 'Seller' });
        }
    }, [initialRecipientId]);

    const handleSendMessage = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newMessage.trim() || !selectedUser) return;

        setSending(true);
        try {
            await api.sendMessage(selectedUser.id, newMessage, initialListingId || undefined);
            setNewMessage('');
            loadThread(selectedUser.id);
            loadConversations();
        } catch (err) {
            console.error('Failed to send message', err);
        } finally {
            setSending(false);
        }
    };

    const sendQuickMessage = (text: string) => {
        setNewMessage(text);
    };

    if (!currentUser) {
        return (
            <div className="bg-white rounded-3xl p-12 text-center max-w-md mx-auto border border-slate-200 shadow-sm space-y-4">
                <MessageSquare className="w-12 h-12 text-slate-400 mx-auto" />
                <h3 className="text-lg font-bold text-slate-900">Sign in to view messages</h3>
                <p className="text-xs text-slate-500">
                    Direct in-app messaging allows buyers and sellers to communicate securely in real time.
                </p>
            </div>
        );
    }

    return (
        <div className="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden h-[700px] flex flex-col md:flex-row">
            {/* Conversations Sidebar */}
            <div className="w-full md:w-80 border-r border-slate-100 flex flex-col bg-slate-50/50">
                <div className="p-4 border-b border-slate-200/80 bg-white">
                    <h2 className="text-sm font-black text-slate-900 flex items-center gap-2">
                        <MessageSquare className="w-4 h-4 text-emerald-600" />
                        <span>Conversations</span>
                    </h2>
                </div>

                <div className="flex-1 overflow-y-auto p-2 space-y-1">
                    {loading ? (
                        <div className="p-4 text-center text-xs text-slate-400">Loading chats...</div>
                    ) : conversations.length === 0 ? (
                        <div className="p-6 text-center text-xs text-slate-400">
                            No conversations yet. Inquire on any listing to start chatting!
                        </div>
                    ) : (
                        conversations.map((conv, idx) => {
                            const isSelected = selectedUser?.id === conv.user?.id;
                            return (
                                <button
                                    key={idx}
                                    onClick={() => setSelectedUser(conv.user)}
                                    className={`w-full p-3 rounded-2xl text-left transition flex items-start gap-3 ${
                                        isSelected 
                                            ? 'bg-emerald-50 border border-emerald-200 text-emerald-950' 
                                            : 'hover:bg-white text-slate-700'
                                    }`}
                                >
                                    <div className="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center shrink-0 text-xs">
                                        {conv.user?.name?.charAt(0) || 'U'}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between">
                                            <span className="text-xs font-bold truncate">{conv.user?.name}</span>
                                            {conv.unread_count > 0 && (
                                                <span className="bg-emerald-600 text-white text-[10px] font-bold px-1.5 py-0.2 rounded-full">
                                                    {conv.unread_count}
                                                </span>
                                            )}
                                        </div>
                                        {conv.listing && (
                                            <p className="text-[10px] text-emerald-700 font-medium truncate">
                                                Re: {conv.listing.title}
                                            </p>
                                        )}
                                        <p className="text-[11px] text-slate-400 truncate mt-0.5">
                                            {conv.last_message}
                                        </p>
                                    </div>
                                </button>
                            );
                        })
                    )}
                </div>
            </div>

            {/* Chat Thread */}
            <div className="flex-1 flex flex-col bg-white">
                {selectedUser ? (
                    <>
                        {/* Thread Header */}
                        <div className="p-4 border-b border-slate-100 flex items-center justify-between bg-white">
                            <div className="flex items-center gap-3">
                                <div className="w-9 h-9 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-xs">
                                    {selectedUser.name?.charAt(0) || 'U'}
                                </div>
                                <div>
                                    <h3 className="text-xs font-bold text-slate-900">{selectedUser.name}</h3>
                                    <span className="text-[10px] text-emerald-600 font-medium flex items-center gap-1">
                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Active Chat
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Message Feed */}
                        <div className="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50/30">
                            {messages.length === 0 ? (
                                <div className="text-center py-12 text-slate-400 text-xs">
                                    Send a message to start this conversation.
                                </div>
                            ) : (
                                messages.map((msg, idx) => {
                                    const isMe = msg.sender_id === currentUser.id;
                                    return (
                                        <div
                                            key={idx}
                                            className={`flex flex-col ${isMe ? 'items-end' : 'items-start'}`}
                                        >
                                            <div
                                                className={`max-w-md p-3 rounded-2xl text-xs leading-relaxed shadow-xs ${
                                                    isMe
                                                        ? 'bg-emerald-600 text-white rounded-br-xs'
                                                        : 'bg-white text-slate-800 border border-slate-200/80 rounded-bl-xs'
                                                }`}
                                            >
                                                {msg.listing && (
                                                    <div className={`mb-1 pb-1 border-b text-[10px] font-semibold flex items-center justify-between gap-2 ${
                                                        isMe ? 'border-emerald-500 text-emerald-100' : 'border-slate-100 text-slate-500'
                                                    }`}>
                                                        <span>Ref: {msg.listing.title}</span>
                                                        <span>ETB {Number(msg.listing.price).toLocaleString()}</span>
                                                    </div>
                                                )}
                                                <p>{msg.message}</p>
                                            </div>
                                            <span className="text-[9px] text-slate-400 mt-1 px-1 flex items-center gap-1">
                                                {new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                {isMe && <CheckCheck className="w-2.5 h-2.5 text-emerald-500" />}
                                            </span>
                                        </div>
                                    );
                                })
                            )}
                        </div>

                        {/* Quick Prompts */}
                        <div className="p-2 border-t border-slate-100 bg-white flex flex-wrap gap-1.5">
                            {[
                                "Is this still available?",
                                "What is your final price in ETB?",
                                "When can I come inspect it?",
                                "Can you send more photos?"
                            ].map((prompt, idx) => (
                                <button
                                    key={idx}
                                    type="button"
                                    onClick={() => sendQuickMessage(prompt)}
                                    className="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-full font-medium transition"
                                >
                                    {prompt}
                                </button>
                            ))}
                        </div>

                        {/* Message Input */}
                        <form onSubmit={handleSendMessage} className="p-3 border-t border-slate-100 bg-white flex gap-2">
                            <input
                                type="text"
                                value={newMessage}
                                onChange={(e) => setNewMessage(e.target.value)}
                                placeholder="Type a message to the seller..."
                                className="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500"
                            />
                            <button
                                type="submit"
                                disabled={sending || !newMessage.trim()}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm shadow-emerald-600/20 disabled:opacity-50"
                            >
                                <Send className="w-3.5 h-3.5" />
                                <span>{sending ? 'Sending...' : 'Send'}</span>
                            </button>
                        </form>
                    </>
                ) : (
                    <div className="flex-1 flex items-center justify-center p-8 text-center text-slate-400 text-xs">
                        Select a conversation from the sidebar or click "Message Seller" on any listing.
                    </div>
                )}
            </div>
        </div>
    );
};

