import React, { useState, useEffect } from 'react';
import { api } from '../services/api';
import { 
    ShieldAlert, Users, Layers, Clock, DollarSign, 
    CheckCircle2, XCircle, Settings, Tag, Sliders, 
    CreditCard, AlertTriangle, Eye, RefreshCw
} from 'lucide-react';

export const AdminDashboardView: React.FC = () => {
    const [subTab, setSubTab] = useState<'overview' | 'approvals' | 'users' | 'categories' | 'plans' | 'transactions' | 'gateway'>('overview');

    const [dashboardData, setDashboardData] = useState<any>(null);
    const [pendingListings, setPendingListings] = useState<any[]>([]);
    const [usersList, setUsersList] = useState<any[]>([]);
    const [categoriesList, setCategoriesList] = useState<any[]>([]);
    const [plansList, setPlansList] = useState<any[]>([]);
    const [transactionsList, setTransactionsList] = useState<any[]>([]);
    const [gatewayData, setGatewayData] = useState<any>(null);

    const [loading, setLoading] = useState(true);

    // Rejection modal state
    const [rejectingListingId, setRejectingListingId] = useState<number | null>(null);
    const [rejectionReason, setRejectionReason] = useState('');
    const [rejectLoading, setRejectLoading] = useState(false);

    // Category modal state
    const [newCategoryName, setNewCategoryName] = useState('');
    const [newCategoryType, setNewCategoryType] = useState('vehicle');

    const loadDashboard = async () => {
        setLoading(true);
        try {
            const [dash, pending] = await Promise.all([
                api.getAdminDashboard(),
                api.getPendingListings(),
            ]);
            setDashboardData(dash);
            setPendingListings(pending.data || []);
        } catch (err) {
            console.error('Failed to load admin dashboard', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadDashboard();
    }, []);

    const loadUsers = async () => {
        const data = await api.getAdminUsers();
        setUsersList(data.data || []);
    };

    const loadCategories = async () => {
        const data = await api.getAdminCategories();
        setCategoriesList(data || []);
    };

    const loadPlans = async () => {
        const data = await api.getAdminPlans();
        setPlansList(data || []);
    };

    const loadTransactions = async () => {
        const data = await api.getAdminTransactions();
        setTransactionsList(data.data || []);
    };

    const loadGateway = async () => {
        const data = await api.getGatewaySettings();
        setGatewayData(data);
    };

    const handleTabSwitch = (tab: any) => {
        setSubTab(tab);
        if (tab === 'users') loadUsers();
        if (tab === 'categories') loadCategories();
        if (tab === 'plans') loadPlans();
        if (tab === 'transactions') loadTransactions();
        if (tab === 'gateway') loadGateway();
        if (tab === 'approvals' || tab === 'overview') loadDashboard();
    };

    const handleApprove = async (id: number) => {
        if (!confirm('Approve and publish this listing to the marketplace?')) return;
        try {
            await api.approveListing(id);
            loadDashboard();
        } catch (err) {
            alert('Failed to approve listing.');
        }
    };

    const handleRejectSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!rejectingListingId || !rejectionReason.trim()) return;

        setRejectLoading(true);
        try {
            await api.rejectListing(rejectingListingId, rejectionReason);
            setRejectingListingId(null);
            setRejectionReason('');
            loadDashboard();
        } catch (err) {
            alert('Failed to reject listing.');
        } finally {
            setRejectLoading(false);
        }
    };

    const handleToggleUser = async (userId: number) => {
        try {
            await api.toggleUserStatus(userId);
            loadUsers();
        } catch (err: any) {
            alert(err.response?.data?.message || 'Failed to update user status.');
        }
    };

    const handleCreateCategory = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newCategoryName.trim()) return;
        try {
            await api.createCategory({ name: newCategoryName, type: newCategoryType });
            setNewCategoryName('');
            loadCategories();
        } catch (err) {
            alert('Failed to create category.');
        }
    };

    const handleUpdatePlan = async (planId: number, field: string, val: any) => {
        try {
            await api.updateAdminPlan(planId, { [field]: val });
            loadPlans();
        } catch (err) {
            alert('Failed to update plan setting.');
        }
    };

    const handleUpdateGateway = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!gatewayData) return;
        try {
            await api.updateGatewaySettings({
                enabled: gatewayData.enabled,
                mode: gatewayData.mode,
                public_key: gatewayData.public_key,
            });
            alert('Chapa Gateway settings saved successfully.');
        } catch (err) {
            alert('Failed to update gateway settings.');
        }
    };

    const metrics = dashboardData?.metrics;

    return (
        <div className="space-y-6 pb-20">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <ShieldAlert className="w-6 h-6 text-rose-600" />
                        <span>Super Admin Command Center</span>
                    </h1>
                    <p className="text-xs text-slate-500">
                        Platform listings moderation, Ethiopian payment gateway configuration, users & categories management.
                    </p>
                </div>
                <button
                    onClick={loadDashboard}
                    className="p-2 bg-white border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 text-xs font-bold flex items-center gap-1 self-start sm:self-auto"
                >
                    <RefreshCw className="w-3.5 h-3.5" />
                    <span>Refresh</span>
                </button>
            </div>

            {/* Sub Tabs */}
            <div className="flex gap-2 overflow-x-auto pb-1 border-b border-slate-200 text-xs font-bold">
                {[
                    { key: 'overview', label: 'Dashboard Overview' },
                    { key: 'approvals', label: `Approvals Queue (${metrics?.pending_approvals_count ?? 0})` },
                    { key: 'users', label: 'User Accounts' },
                    { key: 'categories', label: 'Categories' },
                    { key: 'plans', label: 'Subscription Plans' },
                    { key: 'transactions', label: 'All Payments' },
                    { key: 'gateway', label: 'Chapa Gateway' },
                ].map(t => (
                    <button
                        key={t.key}
                        onClick={() => handleTabSwitch(t.key)}
                        className={`px-3 py-2 rounded-xl transition whitespace-nowrap ${
                            subTab === t.key
                                ? 'bg-slate-900 text-white'
                                : 'text-slate-600 hover:bg-slate-100'
                        }`}
                    >
                        {t.label}
                    </button>
                ))}
            </div>

            {/* TAB: OVERVIEW */}
            {subTab === 'overview' && (
                <div className="space-y-6">
                    {/* Metrics Grid */}
                    <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1">
                            <div className="flex items-center justify-between text-slate-400">
                                <span className="text-[11px] font-bold uppercase">Total Users</span>
                                <Users className="w-4 h-4 text-emerald-600" />
                            </div>
                            <p className="text-2xl font-black text-slate-900">{metrics?.total_users ?? 0}</p>
                        </div>

                        <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1">
                            <div className="flex items-center justify-between text-slate-400">
                                <span className="text-[11px] font-bold uppercase">Pending Queue</span>
                                <Clock className="w-4 h-4 text-amber-600" />
                            </div>
                            <p className="text-2xl font-black text-amber-600">{metrics?.pending_approvals_count ?? 0}</p>
                        </div>

                        <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1">
                            <div className="flex items-center justify-between text-slate-400">
                                <span className="text-[11px] font-bold uppercase">Active Subs</span>
                                <Sliders className="w-4 h-4 text-blue-600" />
                            </div>
                            <p className="text-2xl font-black text-slate-900">{metrics?.active_subscriptions ?? 0}</p>
                        </div>

                        <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1">
                            <div className="flex items-center justify-between text-slate-400">
                                <span className="text-[11px] font-bold uppercase">Total Listings</span>
                                <Layers className="w-4 h-4 text-indigo-600" />
                            </div>
                            <p className="text-2xl font-black text-slate-900">{metrics?.total_listings ?? 0}</p>
                        </div>

                        <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-1 col-span-2 md:col-span-1">
                            <div className="flex items-center justify-between text-slate-400">
                                <span className="text-[11px] font-bold uppercase">Revenue (Month)</span>
                                <DollarSign className="w-4 h-4 text-emerald-600" />
                            </div>
                            <p className="text-xl sm:text-2xl font-black text-emerald-700">
                                ETB {Number(metrics?.revenue_this_month_etb ?? 0).toLocaleString()}
                            </p>
                        </div>
                    </div>

                    {/* Listings by Type Breakdown */}
                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs space-y-3">
                        <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400">Listings Breakdown By Type</h3>
                        <div className="grid grid-cols-3 gap-4 text-center">
                            <div className="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span className="text-xs text-slate-500 font-semibold">Vehicles</span>
                                <p className="text-xl font-extrabold text-slate-900">{metrics?.listings_by_type?.vehicle ?? 0}</p>
                            </div>
                            <div className="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span className="text-xs text-slate-500 font-semibold">Real Estate</span>
                                <p className="text-xl font-extrabold text-slate-900">{metrics?.listings_by_type?.real_estate ?? 0}</p>
                            </div>
                            <div className="p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span className="text-xs text-slate-500 font-semibold">Apartments</span>
                                <p className="text-xl font-extrabold text-slate-900">{metrics?.listings_by_type?.apartment ?? 0}</p>
                            </div>
                        </div>
                    </div>

                    {/* Pending Approvals Quick Table */}
                    <div className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                        <div className="p-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 className="font-extrabold text-slate-900 text-sm">Awaiting Moderation</h3>
                            <button
                                onClick={() => handleTabSwitch('approvals')}
                                className="text-xs font-bold text-emerald-700 hover:underline"
                            >
                                View All ({pendingListings.length}) →
                            </button>
                        </div>

                        {pendingListings.length > 0 ? (
                            <div className="divide-y divide-slate-100 text-xs">
                                {pendingListings.slice(0, 4).map((listing: any) => (
                                    <div key={listing.id} className="p-4 flex items-center justify-between gap-4">
                                        <div className="flex items-center gap-3">
                                            <img
                                                src={listing.primary_image || listing.images?.[0]?.image_path || 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=800&auto=format&fit=crop&q=70'}
                                                alt=""
                                                className="w-12 h-10 rounded-lg object-cover bg-slate-100"
                                            />
                                            <div>
                                                <h4 className="font-bold text-slate-900 line-clamp-1">{listing.title}</h4>
                                                <p className="text-[11px] text-slate-500">
                                                    by {listing.user?.name} • {listing.city} • ETB {Number(listing.price).toLocaleString()}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <button
                                                onClick={() => handleApprove(listing.id)}
                                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs"
                                            >
                                                Approve
                                            </button>
                                            <button
                                                onClick={() => {
                                                    setRejectingListingId(listing.id);
                                                    setRejectionReason('');
                                                }}
                                                className="bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold px-3 py-1.5 rounded-lg text-xs"
                                            >
                                                Reject...
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="p-8 text-center text-xs text-slate-400">
                                Approvals queue is clear! All submitted listings have been reviewed.
                            </p>
                        )}
                    </div>
                </div>
            )}

            {/* TAB: APPROVALS QUEUE */}
            {subTab === 'approvals' && (
                <div className="space-y-4">
                    <h3 className="font-extrabold text-slate-900 text-base">Listing Approvals Queue</h3>
                    {pendingListings.length > 0 ? (
                        <div className="space-y-4">
                            {pendingListings.map(listing => (
                                <div key={listing.id} className="bg-white rounded-2xl border border-slate-200 p-5 space-y-4 shadow-xs">
                                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <span className="text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded">
                                                    {listing.type}
                                                </span>
                                                <span className="text-xs text-slate-400">
                                                    Submitted: {new Date(listing.created_at).toLocaleString()}
                                                </span>
                                            </div>
                                            <h4 className="font-extrabold text-slate-900 text-base mt-1">{listing.title}</h4>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <button
                                                onClick={() => handleApprove(listing.id)}
                                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 shadow-sm"
                                            >
                                                <CheckCircle2 className="w-4 h-4" />
                                                <span>Approve & Publish</span>
                                            </button>
                                            <button
                                                onClick={() => {
                                                    setRejectingListingId(listing.id);
                                                    setRejectionReason('');
                                                }}
                                                className="bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5"
                                            >
                                                <XCircle className="w-4 h-4" />
                                                <span>Reject With Reason</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                                        <div className="space-y-1">
                                            <span className="text-slate-400 font-bold uppercase text-[10px]">Seller</span>
                                            <p className="font-bold text-slate-800">{listing.user?.name}</p>
                                            <p className="text-slate-500">{listing.user?.email}</p>
                                            <p className="text-slate-500">{listing.user?.phone || 'No phone'}</p>
                                        </div>

                                        <div className="space-y-1">
                                            <span className="text-slate-400 font-bold uppercase text-[10px]">Price & City</span>
                                            <p className="font-black text-emerald-700 text-sm">
                                                ETB {Number(listing.price).toLocaleString()}
                                            </p>
                                            <p className="text-slate-600">{listing.city}</p>
                                            <p className="text-slate-400">{listing.address || 'No specific address'}</p>
                                        </div>

                                        <div className="md:col-span-2 space-y-1">
                                            <span className="text-slate-400 font-bold uppercase text-[10px]">Attributes & Details</span>
                                            <p className="text-slate-600 line-clamp-2">{listing.description || 'No description'}</p>
                                            {listing.listing_attributes && (
                                                <div className="flex flex-wrap gap-1 pt-1">
                                                    {Object.entries(listing.listing_attributes).map(([k, v]) => (
                                                        <span key={k} className="bg-slate-100 px-2 py-0.5 rounded text-[11px] text-slate-600">
                                                            {k}: {String(v)}
                                                        </span>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="bg-white p-12 rounded-3xl border border-slate-200 text-center text-xs text-slate-500">
                            No listings waiting for approval.
                        </div>
                    )}
                </div>
            )}

            {/* TAB: USERS */}
            {subTab === 'users' && (
                <div className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div className="p-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 className="font-extrabold text-slate-900 text-sm">Platform Users ({usersList.length})</h3>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead>
                                <tr className="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                    <th className="p-3">User</th>
                                    <th className="p-3">Email</th>
                                    <th className="p-3">Phone</th>
                                    <th className="p-3">Role</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {usersList.map(u => (
                                    <tr key={u.id} className="hover:bg-slate-50/50">
                                        <td className="p-3 font-bold text-slate-900 flex items-center gap-2">
                                            <div className="w-7 h-7 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold">
                                                {u.name.charAt(0)}
                                            </div>
                                            <div>
                                                <span>{u.name}</span>
                                                <span className="text-[10px] text-slate-400 block font-normal">@{u.username || 'user'}</span>
                                            </div>
                                        </td>
                                        <td className="p-3 text-slate-600">{u.email}</td>
                                        <td className="p-3 text-slate-600">{u.phone || '—'}</td>
                                        <td className="p-3">
                                            <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                                                u.role === 'SUPER_ADMIN' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700'
                                            }`}>
                                                {u.role}
                                            </span>
                                        </td>
                                        <td className="p-3">
                                            <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                                                u.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                                            }`}>
                                                {u.is_active ? 'Active' : 'Suspended'}
                                            </span>
                                        </td>
                                        <td className="p-3 text-right">
                                            {u.role !== 'SUPER_ADMIN' && (
                                                <button
                                                    onClick={() => handleToggleUser(u.id)}
                                                    className={`px-3 py-1 rounded-lg font-bold text-xs ${
                                                        u.is_active 
                                                            ? 'bg-rose-50 text-rose-700 hover:bg-rose-100' 
                                                            : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                                    }`}
                                                >
                                                    {u.is_active ? 'Suspend' : 'Activate'}
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* TAB: CATEGORIES */}
            {subTab === 'categories' && (
                <div className="space-y-6">
                    {/* Add Category Form */}
                    <form onSubmit={handleCreateCategory} className="bg-white p-5 rounded-2xl border border-slate-200 flex flex-col sm:flex-row items-end gap-3 text-xs shadow-xs">
                        <div className="flex-1 w-full">
                            <label className="block font-bold text-slate-700 mb-1">New Category Name</label>
                            <input
                                type="text"
                                required
                                value={newCategoryName}
                                onChange={(e) => setNewCategoryName(e.target.value)}
                                placeholder="e.g. Commercial Office Spaces"
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                            />
                        </div>
                        <div className="w-full sm:w-48">
                            <label className="block font-bold text-slate-700 mb-1">Type</label>
                            <select
                                value={newCategoryType}
                                onChange={(e) => setNewCategoryType(e.target.value)}
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                            >
                                <option value="vehicle">Vehicle</option>
                                <option value="real_estate">Real Estate</option>
                                <option value="apartment">Apartment</option>
                            </select>
                        </div>
                        <button
                            type="submit"
                            className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-xl transition text-xs shrink-0 w-full sm:w-auto"
                        >
                            + Add Category
                        </button>
                    </form>

                    {/* Category List */}
                    <div className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                        <div className="p-4 border-b border-slate-100">
                            <h3 className="font-extrabold text-slate-900 text-sm">Flat Category Catalog</h3>
                        </div>
                        <div className="divide-y divide-slate-100 text-xs">
                            {categoriesList.map(cat => (
                                <div key={cat.id} className="p-3.5 px-4 flex items-center justify-between">
                                    <div>
                                        <span className="font-bold text-slate-900 text-sm">{cat.name}</span>
                                        <span className="text-[11px] text-slate-400 block">slug: {cat.slug}</span>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <span className="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase">
                                            {cat.type}
                                        </span>
                                        <span className="text-slate-500 text-xs">
                                            {cat.listings_count || 0} listings
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            {/* TAB: SUBSCRIPTION PLANS SETTINGS */}
            {subTab === 'plans' && (
                <div className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div className="p-4 border-b border-slate-100">
                        <h3 className="font-extrabold text-slate-900 text-sm">Subscription Plans & Listing Limits</h3>
                        <p className="text-xs text-slate-500">Edit listing quotas and monthly prices for Basic, Premium, and Pro tiers.</p>
                    </div>
                    <div className="divide-y divide-slate-100 text-xs">
                        {plansList.map(plan => (
                            <div key={plan.id} className="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <h4 className="font-black text-slate-900 text-sm">{plan.name}</h4>
                                    <p className="text-slate-400 text-[11px]">Billing: {plan.billing_period}</p>
                                </div>

                                <div className="flex flex-wrap items-center gap-4">
                                    <div>
                                        <label className="block text-[10px] font-bold uppercase text-slate-400 mb-1">Monthly Price (ETB)</label>
                                        <input
                                            type="number"
                                            defaultValue={plan.price}
                                            onBlur={(e) => handleUpdatePlan(plan.id, 'price', e.target.value)}
                                            className="bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-xs w-28 font-bold"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-[10px] font-bold uppercase text-slate-400 mb-1">Listing Limit Quota</label>
                                        <input
                                            type="number"
                                            defaultValue={plan.listing_limit}
                                            onBlur={(e) => handleUpdatePlan(plan.id, 'listing_limit', e.target.value)}
                                            className="bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-xs w-24 font-bold"
                                        />
                                    </div>

                                    <div className="self-end pb-1 text-slate-400 text-[11px]">
                                        {plan.subscriptions_count ?? 0} active subscribers
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* TAB: TRANSACTIONS */}
            {subTab === 'transactions' && (
                <div className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                    <div className="p-4 border-b border-slate-100">
                        <h3 className="font-extrabold text-slate-900 text-sm">All Platform Transactions ({transactionsList.length})</h3>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead>
                                <tr className="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                    <th className="p-3">Reference</th>
                                    <th className="p-3">User</th>
                                    <th className="p-3">Plan</th>
                                    <th className="p-3">Amount</th>
                                    <th className="p-3">Gateway</th>
                                    <th className="p-3">Date</th>
                                    <th className="p-3 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {transactionsList.map(tx => (
                                    <tr key={tx.id} className="hover:bg-slate-50/50">
                                        <td className="p-3 font-mono text-slate-700 font-medium">{tx.transaction_reference}</td>
                                        <td className="p-3 font-bold text-slate-900">{tx.user?.name}</td>
                                        <td className="p-3">{tx.plan?.name || 'SaaS Plan'}</td>
                                        <td className="p-3 font-black text-emerald-700">ETB {Number(tx.amount).toLocaleString()}</td>
                                        <td className="p-3 capitalize">{tx.provider}</td>
                                        <td className="p-3 text-slate-500">{new Date(tx.created_at).toLocaleDateString()}</td>
                                        <td className="p-3 text-right">
                                            <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                                                tx.status === 'completed' || tx.status === 'verified'
                                                    ? 'bg-emerald-100 text-emerald-800'
                                                    : 'bg-amber-100 text-amber-800'
                                            }`}>
                                                {tx.status}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* TAB: GATEWAY SETTINGS */}
            {subTab === 'gateway' && (
                <div className="max-w-2xl bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-xs space-y-5">
                    <div>
                        <h3 className="font-extrabold text-slate-900 text-base">Chapa Payment Gateway Settings</h3>
                        <p className="text-xs text-slate-500">Configure Chapa API credentials and test/live modes for Ethiopia payments.</p>
                    </div>

                    {gatewayData && (
                        <form onSubmit={handleUpdateGateway} className="space-y-4 text-xs">
                            <div className="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-200">
                                <div>
                                    <span className="font-bold text-slate-900 block">Enable Chapa Gateway</span>
                                    <span className="text-[11px] text-slate-500">Allow users to pay via Telebirr, CBE Birr, and cards</span>
                                </div>
                                <input
                                    type="checkbox"
                                    checked={gatewayData.enabled}
                                    onChange={(e) => setGatewayData({ ...gatewayData, enabled: e.target.checked })}
                                    className="w-5 h-5 text-emerald-600 rounded"
                                />
                            </div>

                            <div>
                                <label className="block text-slate-700 font-bold mb-1">Environment Mode</label>
                                <select
                                    value={gatewayData.mode}
                                    onChange={(e) => setGatewayData({ ...gatewayData, mode: e.target.value })}
                                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold"
                                >
                                    <option value="test">Sandbox / Test Mode</option>
                                    <option value="live">Live Production</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-slate-700 font-bold mb-1">Chapa Public Key</label>
                                <input
                                    type="text"
                                    value={gatewayData.public_key || ''}
                                    onChange={(e) => setGatewayData({ ...gatewayData, public_key: e.target.value })}
                                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono"
                                />
                            </div>

                            <div>
                                <label className="block text-slate-700 font-bold mb-1">Chapa Secret Key (Stored Encrypted)</label>
                                <input
                                    type="password"
                                    placeholder={gatewayData.has_secret_key ? '••••••••••••••••' : 'Enter secret key'}
                                    onChange={(e) => setGatewayData({ ...gatewayData, secret_key: e.target.value })}
                                    className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono"
                                />
                            </div>

                            <button
                                type="submit"
                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-2.5 rounded-xl transition shadow-sm"
                            >
                                Save Gateway Settings
                            </button>
                        </form>
                    )}
                </div>
            )}

            {/* REJECTION REASON MODAL */}
            {rejectingListingId && (
                <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
                    <div className="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl border border-slate-200">
                        <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h3 className="font-bold text-slate-900 text-sm">Reject Listing with Reason</h3>
                            <button
                                onClick={() => setRejectingListingId(null)}
                                className="text-slate-400 hover:text-slate-700"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleRejectSubmit} className="space-y-3 text-xs">
                            <p className="text-slate-500 text-[11px]">
                                Provide a clear, constructive reason so the seller can correct issues and resubmit. The seller will be notified immediately.
                            </p>

                            <textarea
                                required
                                rows={4}
                                value={rejectionReason}
                                onChange={(e) => setRejectionReason(e.target.value)}
                                placeholder="e.g. Please upload clear original photos of the vehicle interior and confirm the chassis number."
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none resize-none"
                            ></textarea>

                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setRejectingListingId(null)}
                                    className="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-bold"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={rejectLoading || !rejectionReason.trim()}
                                    className="bg-rose-600 hover:bg-rose-700 text-white font-bold px-5 py-2 rounded-xl disabled:opacity-50"
                                >
                                    {rejectLoading ? 'Rejecting...' : 'Confirm Rejection'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

