import React, { useState, useEffect } from 'react';
import { CrmLead, CrmLeadStage } from '../types/marketplace';
import { api } from '../services/api';
import { 
    Inbox, Mail, Phone, Clock, CheckCircle2, 
    ArrowRight, Trash2, ExternalLink, MessageSquare, AlertCircle
} from 'lucide-react';

export const CrmLeadsView: React.FC = () => {
    const [leads, setLeads] = useState<CrmLead[]>([]);
    const [counts, setCounts] = useState<Record<string, number>>({});
    const [activeStage, setActiveStage] = useState<string>('all');
    const [loading, setLoading] = useState(true);

    const fetchLeads = async () => {
        setLoading(true);
        try {
            const params: Record<string, any> = {};
            if (activeStage !== 'all') {
                params.status = activeStage;
            }
            const res = await api.getCrmLeads(params);
            setLeads(res.data.data || []);
            setCounts(res.counts || {});
        } catch (err) {
            console.error('Failed to load CRM leads', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchLeads();
    }, [activeStage]);

    const handleUpdateStage = async (leadId: number, newStage: CrmLeadStage) => {
        try {
            await api.updateLeadStatus(leadId, newStage);
            fetchLeads();
        } catch (err) {
            alert('Failed to update lead status.');
        }
    };

    const handleDelete = async (leadId: number) => {
        if (!confirm('Are you sure you want to remove this lead?')) return;
        try {
            await api.deleteLead(leadId);
            fetchLeads();
        } catch (err) {
            alert('Failed to delete lead.');
        }
    };

    const getStageBadge = (status: CrmLeadStage) => {
        switch (status) {
            case 'New':
                return (
                    <span className="bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full">
                        ● New Lead
                    </span>
                );
            case 'Contacted':
                return (
                    <span className="bg-blue-100 text-blue-800 text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full">
                        ● Contacted
                    </span>
                );
            case 'Closed':
                return (
                    <span className="bg-slate-200 text-slate-700 text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full">
                        ✓ Closed Deal
                    </span>
                );
            default:
                return null;
        }
    };

    return (
        <div className="space-y-6 pb-16">
            {/* Header */}
            <div>
                <h1 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <Inbox className="w-6 h-6 text-emerald-600" />
                    <span>Inbound CRM Leads Pipeline</span>
                </h1>
                <p className="text-xs text-slate-500">
                    Buyers inquiring about your listings are automatically routed here. Manage their pipeline stages (3 stages: New → Contacted → Closed).
                </p>
            </div>

            {/* Pipeline Stage Tabs */}
            <div className="flex gap-2 overflow-x-auto pb-1 border-b border-slate-200 text-xs font-bold">
                {[
                    { key: 'all', label: 'All Inbound Leads', count: counts.total || 0 },
                    { key: 'New', label: 'New Inquiries', count: counts.new || 0 },
                    { key: 'Contacted', label: 'Contacted', count: counts.contacted || 0 },
                    { key: 'Closed', label: 'Closed Deals', count: counts.closed || 0 },
                ].map(tab => (
                    <button
                        key={tab.key}
                        onClick={() => setActiveStage(tab.key)}
                        className={`px-3 py-2 rounded-xl transition whitespace-nowrap flex items-center gap-1.5 ${
                            activeStage === tab.key
                                ? 'bg-slate-900 text-white'
                                : 'text-slate-600 hover:bg-slate-100'
                        }`}
                    >
                        <span>{tab.label}</span>
                        <span className={`text-[10px] px-1.5 py-0.2 rounded-full ${
                            activeStage === tab.key ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'
                        }`}>
                            {tab.count}
                        </span>
                    </button>
                ))}
            </div>

            {/* Leads List */}
            {loading ? (
                <div className="space-y-3">
                    {[1, 2, 3].map(n => (
                        <div key={n} className="bg-white p-5 rounded-2xl border border-slate-200 h-28 animate-pulse"></div>
                    ))}
                </div>
            ) : leads.length > 0 ? (
                <div className="space-y-3">
                    {leads.map(lead => (
                        <div
                            key={lead.id}
                            className="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs hover:border-slate-300 transition space-y-4"
                        >
                            {/* Lead Header */}
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                <div className="flex items-center gap-3">
                                    <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white flex items-center justify-center font-black text-sm">
                                        {lead.buyer_name ? lead.buyer_name.charAt(0).toUpperCase() : 'B'}
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <h3 className="font-extrabold text-slate-900 text-sm">{lead.buyer_name}</h3>
                                            {getStageBadge(lead.status)}
                                        </div>
                                        <p className="text-[11px] text-slate-400">
                                            Received on {new Date(lead.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                        </p>
                                    </div>
                                </div>

                                {/* Pipeline Stage Transition Buttons */}
                                <div className="flex items-center gap-1.5 self-start sm:self-auto bg-slate-50 p-1 rounded-xl border border-slate-200">
                                    <span className="text-[10px] font-bold text-slate-400 px-2 uppercase">Move to:</span>
                                    {(['New', 'Contacted', 'Closed'] as CrmLeadStage[]).map(st => (
                                        <button
                                            key={st}
                                            onClick={() => handleUpdateStage(lead.id, st)}
                                            className={`px-2.5 py-1 rounded-lg text-xs font-bold transition ${
                                                lead.status === st
                                                    ? 'bg-white text-slate-900 shadow-xs ring-1 ring-slate-200'
                                                    : 'text-slate-500 hover:text-slate-800'
                                            }`}
                                        >
                                            {st}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Middle: Inquiry Message & Listing Reference */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                                {/* Message */}
                                <div className="md:col-span-2 bg-slate-50 p-3.5 rounded-xl border border-slate-100 space-y-1">
                                    <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1">
                                        <MessageSquare className="w-3 h-3" />
                                        <span>Buyer Message</span>
                                    </span>
                                    <p className="text-slate-700 whitespace-pre-line leading-relaxed font-medium">
                                        "{lead.message}"
                                    </p>
                                </div>

                                {/* Target Listing */}
                                {lead.listing && (
                                    <div className="bg-emerald-50/50 p-3.5 rounded-xl border border-emerald-100/80 flex items-center gap-3">
                                        {lead.listing.primary_image && (
                                            <img
                                                src={lead.listing.primary_image}
                                                alt={lead.listing.title}
                                                className="w-12 h-12 rounded-lg object-cover shrink-0 bg-slate-100"
                                            />
                                        )}
                                        <div className="truncate">
                                            <span className="text-[10px] font-bold text-emerald-700 uppercase">Listing</span>
                                            <h4 className="font-bold text-slate-800 text-xs truncate">{lead.listing.title}</h4>
                                            <p className="text-emerald-800 font-extrabold text-xs">
                                                {lead.listing.currency} {Number(lead.listing.price).toLocaleString()}
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Contact Action Footer */}
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 text-xs">
                                <div className="flex flex-wrap items-center gap-4 text-slate-600 font-medium">
                                    <a
                                        href={`mailto:${lead.buyer_email}`}
                                        className="flex items-center gap-1.5 text-emerald-700 hover:underline font-bold"
                                    >
                                        <Mail className="w-3.5 h-3.5" />
                                        <span>{lead.buyer_email}</span>
                                    </a>

                                    {lead.buyer_phone && (
                                        <a
                                            href={`tel:${lead.buyer_phone}`}
                                            className="flex items-center gap-1.5 text-slate-800 hover:text-emerald-700 font-bold"
                                        >
                                            <Phone className="w-3.5 h-3.5" />
                                            <span>{lead.buyer_phone}</span>
                                        </a>
                                    )}
                                </div>

                                <button
                                    onClick={() => handleDelete(lead.id)}
                                    className="text-slate-400 hover:text-rose-600 p-1 transition self-end sm:self-auto"
                                    title="Delete Lead"
                                >
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="bg-white rounded-3xl border border-slate-200 p-12 text-center max-w-md mx-auto space-y-3">
                    <Inbox className="w-10 h-10 text-slate-300 mx-auto" />
                    <h3 className="font-bold text-slate-900 text-base">No CRM Leads Found</h3>
                    <p className="text-xs text-slate-500">
                        When interested buyers click "Contact Seller" on your listings, their inquiries will automatically appear in this pipeline.
                    </p>
                </div>
            )}
        </div>
    );
};

