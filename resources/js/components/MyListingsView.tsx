import React, { useState, useEffect } from 'react';
import { Listing, User } from '../types/marketplace';
import { api } from '../services/api';
import { 
    PlusCircle, Trash2, Edit3, CheckCircle, AlertTriangle, 
    Clock, Sparkles, MapPin, Eye, ExternalLink, ChevronRight, XCircle
} from 'lucide-react';

interface MyListingsViewProps {
    user: User;
    onOpenPostListing: () => void;
    onNavigatePricing: () => void;
    onSelectListing: (listing: Listing) => void;
}

export const MyListingsView: React.FC<MyListingsViewProps> = ({
    user,
    onOpenPostListing,
    onNavigatePricing,
    onSelectListing,
}) => {
    const [listings, setListings] = useState<Listing[]>([]);
    const [counts, setCounts] = useState<Record<string, number>>({});
    const [quota, setQuota] = useState(user.quota);
    const [activeTab, setActiveTab] = useState<string>('all');
    const [loading, setLoading] = useState(true);

    const fetchMyListings = async () => {
        setLoading(true);
        try {
            const params: Record<string, any> = {};
            if (activeTab !== 'all') {
                params.status = activeTab;
            }
            const res = await api.getMyListings(params);
            setListings(res.data.data || []);
            setCounts(res.counts || {});
            if (res.quota) setQuota(res.quota);
        } catch (err) {
            console.error('Failed to load my listings', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchMyListings();
    }, [activeTab]);

    const handleDelete = async (id: number) => {
        if (!confirm('Are you sure you want to delete this listing?')) return;
        try {
            await api.deleteListing(id);
            fetchMyListings();
        } catch (err) {
            alert('Failed to delete listing.');
        }
    };

    const handleStatusChange = async (id: number, status: string) => {
        try {
            await api.changeListingStatus(id, status);
            fetchMyListings();
        } catch (err) {
            alert('Failed to update listing status.');
        }
    };

    const formatPrice = (price: number, currency = 'ETB') => {
        return `${currency} ${Number(price).toLocaleString('en-US')}`;
    };

    const getStatusBadge = (listing: Listing) => {
        switch (listing.status) {
            case 'published':
                return (
                    <span className="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        <CheckCircle className="w-3 h-3 text-emerald-600" />
                        <span>Published</span>
                    </span>
                );
            case 'pending':
                return (
                    <span className="inline-flex items-center gap-1 bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        <Clock className="w-3 h-3 text-amber-600" />
                        <span>Pending Approval</span>
                    </span>
                );
            case 'rejected':
                return (
                    <span className="inline-flex items-center gap-1 bg-rose-100 text-rose-800 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        <XCircle className="w-3 h-3 text-rose-600" />
                        <span>Rejected</span>
                    </span>
                );
            case 'sold':
                return (
                    <span className="bg-slate-200 text-slate-800 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        Sold
                    </span>
                );
            case 'rented':
                return (
                    <span className="bg-slate-200 text-slate-800 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        Rented
                    </span>
                );
            default:
                return (
                    <span className="bg-slate-100 text-slate-700 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        Draft
                    </span>
                );
        }
    };

    return (
        <div className="space-y-6 pb-16">
            {/* Header with Title & Add Listing */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">My Listings</h1>
                    <p className="text-xs text-slate-500">Manage all your posted vehicles, real estate, and apartments in one place.</p>
                </div>
                <button
                    onClick={onOpenPostListing}
                    className="bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold px-4 py-2.5 rounded-xl flex items-center gap-1.5 shadow-sm shadow-emerald-600/30 transition self-start sm:self-auto"
                >
                    <PlusCircle className="w-4 h-4" />
                    <span>Post New Listing</span>
                </button>
            </div>

            {/* Quota Progress Card */}
            <div className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div className="space-y-1.5 flex-1">
                    <div className="flex items-center justify-between text-xs max-w-md">
                        <span className="font-bold text-slate-700 flex items-center gap-1.5">
                            <Sparkles className="w-3.5 h-3.5 text-amber-500" />
                            <span>Plan Quota: {quota?.plan_name || 'Basic'} Plan</span>
                        </span>
                        <span className="font-black text-slate-900">
                            {quota?.used ?? 0} / {quota?.limit ?? 20} used
                        </span>
                    </div>

                    {/* Progress bar */}
                    <div className="w-full max-w-md bg-slate-100 h-2.5 rounded-full overflow-hidden">
                        <div
                            className={`h-full transition-all duration-500 ${
                                ((quota?.used ?? 0) / (quota?.limit ?? 20)) >= 1
                                    ? 'bg-rose-500'
                                    : ((quota?.used ?? 0) / (quota?.limit ?? 20)) > 0.8
                                    ? 'bg-amber-500'
                                    : 'bg-emerald-500'
                            }`}
                            style={{ width: `${Math.min(100, (((quota?.used ?? 0) / (quota?.limit ?? 20)) * 100))}%` }}
                        ></div>
                    </div>
                    <p className="text-[11px] text-slate-500">
                        {quota && quota.remaining > 0
                            ? `You can create ${quota.remaining} more listing${quota.remaining > 1 ? 's' : ''} on your current plan.`
                            : 'You have reached your listing quota limit.'}
                    </p>
                </div>

                <button
                    onClick={onNavigatePricing}
                    className="bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-4 py-2 rounded-xl transition flex items-center gap-1 shrink-0 self-start sm:self-auto"
                >
                    <span>Upgrade Quota</span>
                    <ChevronRight className="w-3.5 h-3.5" />
                </button>
            </div>

            {/* Filter Tabs */}
            <div className="flex gap-2 overflow-x-auto pb-1 border-b border-slate-200 text-xs font-bold">
                {[
                    { key: 'all', label: 'All Listings', count: counts.total || 0 },
                    { key: 'published', label: 'Published', count: counts.published || 0 },
                    { key: 'pending', label: 'Pending Approval', count: counts.pending || 0 },
                    { key: 'draft', label: 'Drafts', count: counts.draft || 0 },
                    { key: 'rejected', label: 'Rejected', count: counts.rejected || 0 },
                    { key: 'sold', label: 'Sold / Rented', count: counts.sold_rented || 0 },
                ].map(tab => (
                    <button
                        key={tab.key}
                        onClick={() => setActiveTab(tab.key)}
                        className={`px-3 py-2 rounded-xl transition whitespace-nowrap flex items-center gap-1.5 ${
                            activeTab === tab.key
                                ? 'bg-slate-900 text-white'
                                : 'text-slate-600 hover:bg-slate-100'
                        }`}
                    >
                        <span>{tab.label}</span>
                        <span className={`text-[10px] px-1.5 py-0.2 rounded-full ${
                            activeTab === tab.key ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'
                        }`}>
                            {tab.count}
                        </span>
                    </button>
                ))}
            </div>

            {/* Listings List */}
            {loading ? (
                <div className="space-y-3">
                    {[1, 2, 3].map(n => (
                        <div key={n} className="bg-white p-4 rounded-2xl border border-slate-200 h-24 animate-pulse"></div>
                    ))}
                </div>
            ) : listings.length > 0 ? (
                <div className="space-y-3">
                    {listings.map(listing => (
                        <div
                            key={listing.id}
                            className="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs hover:border-slate-300 transition flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                        >
                            {/* Left: Thumbnail & Info */}
                            <div className="flex items-start gap-4 flex-1">
                                <img
                                    src={listing.primary_image || listing.images?.[0]?.url || 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=800&auto=format&fit=crop&q=70'}
                                    alt={listing.title}
                                    className="w-20 h-16 sm:w-24 sm:h-20 rounded-xl object-cover shrink-0 bg-slate-100 cursor-pointer"
                                    onClick={() => onSelectListing(listing)}
                                />
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        {getStatusBadge(listing)}
                                        <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                            {listing.type.replace('_', ' ')}
                                        </span>
                                    </div>

                                    <h3 
                                        onClick={() => onSelectListing(listing)}
                                        className="font-bold text-slate-900 text-sm hover:text-emerald-700 cursor-pointer transition line-clamp-1"
                                    >
                                        {listing.title}
                                    </h3>

                                    <div className="flex items-center gap-3 text-xs text-slate-500">
                                        <span className="font-extrabold text-emerald-700">
                                            {formatPrice(listing.price, listing.currency)}
                                        </span>
                                        <span>•</span>
                                        <span>{listing.city}</span>
                                        <span>•</span>
                                        <span className="flex items-center gap-1">
                                            <Eye className="w-3 h-3 text-slate-400" />
                                            <span>{listing.views_count} views</span>
                                        </span>
                                    </div>

                                    {/* Rejection Reason notice */}
                                    {listing.status === 'rejected' && listing.rejection_reason && (
                                        <div className="mt-1.5 p-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-[11px] flex items-start gap-1.5">
                                            <AlertTriangle className="w-3.5 h-3.5 shrink-0 text-rose-600 mt-0.5" />
                                            <div>
                                                <span className="font-bold">Admin Rejection Reason: </span>
                                                <span>{listing.rejection_reason}</span>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Right: Actions */}
                            <div className="flex items-center gap-2 w-full sm:w-auto justify-end border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-100">
                                {listing.status === 'published' && (
                                    <>
                                        <button
                                            onClick={() => handleStatusChange(listing.id, listing.type === 'apartment' ? 'rented' : 'sold')}
                                            className="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            Mark {listing.type === 'apartment' ? 'Rented' : 'Sold'}
                                        </button>
                                        <button
                                            onClick={() => onSelectListing(listing)}
                                            className="p-2 rounded-lg text-slate-600 hover:bg-slate-100"
                                            title="View Details"
                                        >
                                            <ExternalLink className="w-4 h-4" />
                                        </button>
                                    </>
                                )}

                                {listing.status === 'draft' && (
                                    <button
                                        onClick={() => handleStatusChange(listing.id, 'pending')}
                                        className="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700"
                                    >
                                        Submit for Approval
                                    </button>
                                )}

                                {listing.status === 'rejected' && (
                                    <button
                                        onClick={() => handleStatusChange(listing.id, 'pending')}
                                        className="px-3 py-1.5 rounded-lg bg-amber-600 text-white text-xs font-bold hover:bg-amber-700"
                                    >
                                        Resubmit
                                    </button>
                                )}

                                <button
                                    onClick={() => handleDelete(listing.id)}
                                    className="p-2 rounded-lg text-rose-600 hover:bg-rose-50 transition"
                                    title="Delete Listing"
                                >
                                    <Trash2 className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="bg-white rounded-3xl border border-slate-200 p-12 text-center max-w-md mx-auto space-y-3">
                    <h3 className="font-bold text-slate-900 text-base">No Listings in this Tab</h3>
                    <p className="text-xs text-slate-500">You don't have any listings with this status.</p>
                    <button
                        onClick={onOpenPostListing}
                        className="bg-emerald-600 text-white text-xs font-bold px-4 py-2 rounded-xl hover:bg-emerald-700 transition"
                    >
                        Create Your First Listing
                    </button>
                </div>
            )}
        </div>
    );
};

