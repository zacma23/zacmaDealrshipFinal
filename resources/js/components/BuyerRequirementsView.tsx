import React, { useState, useEffect } from 'react';
import { BuyerRequirement, ListingType, User } from '../types/marketplace';
import { api } from '../services/api';
import { 
    HelpCircle, Plus, Search, MapPin, DollarSign, 
    Car, Home, Building2, Layers, CheckCircle2, AlertCircle, X, Send 
} from 'lucide-react';

interface BuyerRequirementsViewProps {
    currentUser: User | null;
    onRequireLogin: () => void;
    onDirectMessage: (userId: number) => void;
}

export const BuyerRequirementsView: React.FC<BuyerRequirementsViewProps> = ({
    currentUser,
    onRequireLogin,
    onDirectMessage,
}) => {
    const [requirements, setRequirements] = useState<BuyerRequirement[]>([]);
    const [loading, setLoading] = useState(true);
    const [filterType, setFilterType] = useState<string>('');
    const [filterCity, setFilterCity] = useState<string>('');
    const [modalOpen, setModalOpen] = useState(false);

    // Form fields
    const [type, setType] = useState<ListingType>('vehicle');
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [city, setCity] = useState('Addis Ababa');
    const [budgetMin, setBudgetMin] = useState('');
    const [budgetMax, setBudgetMax] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);

    const loadRequirements = async () => {
        setLoading(true);
        try {
            const params: Record<string, any> = {};
            if (filterType) params.type = filterType;
            if (filterCity) params.city = filterCity;

            const res = await api.getBuyerRequirements(params);
            setRequirements(res?.data || []);
        } catch (err) {
            console.error('Failed to load buyer requirements', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadRequirements();
    }, [filterType, filterCity]);

    const handleCreateRequirement = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!currentUser) {
            onRequireLogin();
            return;
        }

        setSubmitting(true);
        try {
            await api.createBuyerRequirement({
                type,
                title,
                description,
                city,
                budget_min: budgetMin ? parseFloat(budgetMin) : null,
                budget_max: budgetMax ? parseFloat(budgetMax) : null,
            });
            setModalOpen(false);
            setSuccessMessage('Requirement posted! Sellers and dealers will be able to contact you with matching inventory.');
            setTitle('');
            setDescription('');
            setBudgetMin('');
            setBudgetMax('');
            loadRequirements();
        } catch (err) {
            console.error('Failed to post requirement', err);
        } finally {
            setSubmitting(false);
        }
    };

    const getTypeIcon = (t: ListingType) => {
        switch (t) {
            case 'vehicle': return <Car className="w-4 h-4 text-blue-500" />;
            case 'real_estate': return <Home className="w-4 h-4 text-emerald-500" />;
            case 'apartment': return <Building2 className="w-4 h-4 text-amber-500" />;
            case 'product': return <Layers className="w-4 h-4 text-purple-500" />;
            default: return <HelpCircle className="w-4 h-4 text-slate-500" />;
        }
    };

    return (
        <div className="space-y-6 pb-16">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-slate-900 to-slate-950 p-6 sm:p-8 rounded-3xl text-white shadow-lg">
                <div className="space-y-1.5">
                    <div className="inline-flex items-center gap-1.5 bg-emerald-500/20 text-emerald-400 text-xs font-bold px-2.5 py-0.5 rounded-full">
                        <HelpCircle className="w-3.5 h-3.5" />
                        <span>CRM Buyer Requirements Hub</span>
                    </div>
                    <h1 className="text-xl sm:text-3xl font-black tracking-tight">
                        Can't Find What You're Looking For?
                    </h1>
                    <p className="text-xs text-slate-400 max-w-xl">
                        Post your specific vehicle, property, or product requirements in ETB. Verified Ethiopian sellers and dealers will review your request and reach out directly.
                    </p>
                </div>
                <button
                    onClick={() => {
                        if (!currentUser) onRequireLogin();
                        else setModalOpen(true);
                    }}
                    className="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold px-5 py-3 rounded-2xl text-xs transition flex items-center gap-2 shrink-0 shadow-md shadow-emerald-500/20"
                >
                    <Plus className="w-4 h-4" />
                    <span>Post Buyer Request</span>
                </button>
            </div>

            {/* Alert */}
            {successMessage && (
                <div className="bg-emerald-50 border border-emerald-300 text-emerald-900 p-4 rounded-2xl text-xs flex items-center gap-2">
                    <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                    <span>{successMessage}</span>
                </div>
            )}

            {/* Filter Bar */}
            <div className="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200">
                <div className="flex flex-wrap items-center gap-2">
                    <button
                        onClick={() => setFilterType('')}
                        className={`px-3 py-1.5 rounded-xl text-xs font-bold transition ${
                            !filterType ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        }`}
                    >
                        All Needs
                    </button>
                    {(['vehicle', 'real_estate', 'apartment', 'product'] as ListingType[]).map(t => (
                        <button
                            key={t}
                            onClick={() => setFilterType(t)}
                            className={`px-3 py-1.5 rounded-xl text-xs font-bold capitalize flex items-center gap-1.5 transition ${
                                filterType === t ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                            }`}
                        >
                            {getTypeIcon(t)}
                            <span>{t.replace('_', ' ')}</span>
                        </button>
                    ))}
                </div>

                <div className="flex items-center gap-2">
                    <MapPin className="w-3.5 h-3.5 text-slate-400" />
                    <select
                        value={filterCity}
                        onChange={(e) => setFilterCity(e.target.value)}
                        className="bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl px-2.5 py-1.5 focus:outline-none"
                    >
                        <option value="">All Ethiopian Cities</option>
                        <option value="Addis Ababa">Addis Ababa</option>
                        <option value="Hawassa">Hawassa</option>
                        <option value="Adama">Adama</option>
                        <option value="Bahir Dar">Bahir Dar</option>
                        <option value="Dire Dawa">Dire Dawa</option>
                    </select>
                </div>
            </div>

            {/* Requirements Grid */}
            {loading ? (
                <div className="py-20 text-center text-slate-400 text-xs">Loading buyer requests...</div>
            ) : requirements.length === 0 ? (
                <div className="bg-white rounded-3xl p-12 text-center max-w-md mx-auto border border-slate-200 space-y-3">
                    <HelpCircle className="w-10 h-10 text-slate-300 mx-auto" />
                    <h3 className="text-sm font-bold text-slate-700">No buyer requests found</h3>
                    <p className="text-xs text-slate-400">Be the first to post a request for vehicles, properties, or products.</p>
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {requirements.map((req) => (
                        <div key={req.id} className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col justify-between hover:border-emerald-300 transition">
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-lg">
                                        {getTypeIcon(req.type)}
                                        <span>{req.type.replace('_', ' ')}</span>
                                    </span>
                                    <span className="text-[10px] text-slate-400 flex items-center gap-1">
                                        <MapPin className="w-3 h-3" />
                                        <span>{req.city}</span>
                                    </span>
                                </div>

                                <h3 className="text-sm font-bold text-slate-900 leading-snug">
                                    {req.title}
                                </h3>

                                {req.description && (
                                    <p className="text-xs text-slate-500 line-clamp-3">
                                        {req.description}
                                    </p>
                                )}

                                {/* Budget Pill */}
                                <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-2 text-xs font-bold text-emerald-900 flex items-center justify-between">
                                    <span className="text-[10px] uppercase tracking-wider text-emerald-700">Target Budget</span>
                                    <span>
                                        {req.budget_max 
                                            ? `Up to ETB ${Number(req.budget_max).toLocaleString()}` 
                                            : req.budget_min 
                                                ? `From ETB ${Number(req.budget_min).toLocaleString()}` 
                                                : 'Negotiable'}
                                    </span>
                                </div>
                            </div>

                            <div className="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between">
                                <span className="text-[11px] text-slate-500 font-medium">
                                    Posted by: <strong className="text-slate-800">{req.user?.name || 'Buyer'}</strong>
                                </span>
                                {currentUser?.id !== req.user_id && (
                                    <button
                                        onClick={() => onDirectMessage(req.user_id)}
                                        className="bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-3 py-1.5 rounded-xl transition flex items-center gap-1 shadow-xs"
                                    >
                                        <Send className="w-3 h-3" />
                                        <span>Connect</span>
                                    </button>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Post Requirement Modal */}
            {modalOpen && (
                <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
                    <div className="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-slate-200">
                        <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <HelpCircle className="w-4 h-4 text-emerald-600" />
                                <span>Post What You Are Looking For</span>
                            </h2>
                            <button onClick={() => setModalOpen(false)} className="text-slate-400 hover:text-slate-600">
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleCreateRequirement} className="space-y-3 text-xs">
                            <div>
                                <label className="block text-slate-700 font-bold mb-1">Select Industry</label>
                                <div className="grid grid-cols-4 gap-2">
                                    {(['vehicle', 'real_estate', 'apartment', 'product'] as ListingType[]).map(t => (
                                        <button
                                            key={t}
                                            type="button"
                                            onClick={() => setType(t)}
                                            className={`p-2 rounded-xl border text-center capitalize font-bold text-[11px] transition ${
                                                type === t ? 'bg-emerald-50 border-emerald-500 text-emerald-800 ring-2 ring-emerald-500/20' : 'border-slate-200 text-slate-600'
                                            }`}
                                        >
                                            {t.replace('_', ' ')}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div>
                                <label className="block text-slate-700 font-bold mb-1">Title / Short Summary</label>
                                <input
                                    type="text"
                                    required
                                    value={title}
                                    onChange={(e) => setTitle(e.target.value)}
                                    placeholder="e.g. Seeking clean 2018 Toyota RAV4 under 4.2M ETB"
                                    className="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-2">
                                <div>
                                    <label className="block text-slate-700 font-bold mb-1">City</label>
                                    <select
                                        value={city}
                                        onChange={(e) => setCity(e.target.value)}
                                        className="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                    >
                                        <option value="Addis Ababa">Addis Ababa</option>
                                        <option value="Hawassa">Hawassa</option>
                                        <option value="Adama">Adama</option>
                                        <option value="Bahir Dar">Bahir Dar</option>
                                        <option value="Dire Dawa">Dire Dawa</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-slate-700 font-bold mb-1">Max Budget (ETB)</label>
                                    <input
                                        type="number"
                                        value={budgetMax}
                                        onChange={(e) => setBudgetMax(e.target.value)}
                                        placeholder="e.g. 4500000"
                                        className="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-slate-700 font-bold mb-1">Detailed Specifications</label>
                                <textarea
                                    rows={3}
                                    value={description}
                                    onChange={(e) => setDescription(e.target.value)}
                                    placeholder="Mention exact mileage, color, transmission, or property specs you prefer..."
                                    className="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none resize-none"
                                ></textarea>
                            </div>

                            <div className="flex gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setModalOpen(false)}
                                    className="flex-1 bg-slate-100 text-slate-700 font-bold py-2.5 rounded-xl hover:bg-slate-200 transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={submitting}
                                    className="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl transition shadow-sm shadow-emerald-600/20 disabled:opacity-50"
                                >
                                    {submitting ? 'Posting...' : 'Post Requirement'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};
