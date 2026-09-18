import React, { useState, useEffect } from 'react';
import { api } from '../services/api';
import { 
    ShieldAlert, Users, Layers, Clock, DollarSign, 
    CheckCircle2, XCircle, Settings, Tag, Sliders, 
    CreditCard, AlertTriangle, Eye, RefreshCw, Car,
    ToggleLeft, ToggleRight, Plus, ChevronDown, ChevronRight, Shield
} from 'lucide-react';

type AdminSubTab = 'overview' | 'approvals' | 'users' | 'categories' | 'plans' | 'transactions' | 'gateways' | 'vehicle-catalog';

export const AdminDashboardView: React.FC = () => {
    const [subTab, setSubTab] = useState<AdminSubTab>('overview');

    const [dashboardData, setDashboardData] = useState<any>(null);
    const [pendingListings, setPendingListings] = useState<any[]>([]);
    const [usersList, setUsersList] = useState<any[]>([]);
    const [categoriesList, setCategoriesList] = useState<any[]>([]);
    const [plansList, setPlansList] = useState<any[]>([]);
    const [transactionsList, setTransactionsList] = useState<any[]>([]);
    const [gatewaysList, setGatewaysList] = useState<any[]>([]);
    const [vehicleBrands, setVehicleBrands] = useState<any[]>([]);

    const [loading, setLoading] = useState(true);

    // Rejection modal state
    const [rejectingListingId, setRejectingListingId] = useState<number | null>(null);
    const [rejectionReason, setRejectionReason] = useState('');
    const [rejectLoading, setRejectLoading] = useState(false);

    // Category modal state
    const [newCategoryName, setNewCategoryName] = useState('');
    const [newCategoryType, setNewCategoryType] = useState('vehicle');

    // Gateway edit state
    const [editingGatewayId, setEditingGatewayId] = useState<number | null>(null);
    const [gatewayFormData, setGatewayFormData] = useState<Record<string, any>>({});
    const [gatewaySaving, setGatewaySaving] = useState(false);

    // Vehicle catalog state
    const [expandedBrandId, setExpandedBrandId] = useState<number | null>(null);
    const [brandModels, setBrandModels] = useState<Record<number, any[]>>({});
    const [newBrandName, setNewBrandName] = useState('');
    const [newModelForms, setNewModelForms] = useState<Record<number, { name: string; body_type: string }>>({});
    const [catalogLoading, setCatalogLoading] = useState(false);

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

    const loadGateways = async () => {
        try {
            const data = await api.getPaymentGateways();
            setGatewaysList(data || []);
        } catch (err) {
            console.error('Failed to load gateways', err);
        }
    };

    const loadVehicleBrands = async () => {
        setCatalogLoading(true);
        try {
            const data = await api.getAdminVehicleBrands();
            setVehicleBrands(data || []);
        } catch (err) {
            console.error('Failed to load vehicle brands', err);
        } finally {
            setCatalogLoading(false);
        }
    };

    const loadBrandModels = async (brandId: number) => {
        try {
            const data = await api.getAdminVehicleModels(brandId);
            setBrandModels(prev => ({ ...prev, [brandId]: data || [] }));
        } catch (err) {
            console.error('Failed to load models', err);
        }
    };

    const handleTabSwitch = (tab: AdminSubTab) => {
        setSubTab(tab);
        if (tab === 'users') loadUsers();
        if (tab === 'categories') loadCategories();
        if (tab === 'plans') loadPlans();
        if (tab === 'transactions') loadTransactions();
        if (tab === 'gateways') loadGateways();
        if (tab === 'vehicle-catalog') loadVehicleBrands();
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

    // Gateway handlers
    const handleToggleGateway = async (gatewayId: number) => {
        try {
            const data = await api.togglePaymentGateway(gatewayId);
            setGatewaysList(prev => prev.map(g => g.id === gatewayId ? data.data : g));
        } catch (err) {
            alert('Failed to toggle gateway.');
        }
    };

    const handleEditGateway = (gw: any) => {
        setEditingGatewayId(gw.id);
        setGatewayFormData({
            is_test_mode: gw.is_test_mode,
            api_key: '',
            secret_key: '',
            public_key: '',
            merchant_id: '',
            webhook_url: gw.webhook_url || '',
        });
    };

    const handleSaveGateway = async (gatewayId: number) => {
        setGatewaySaving(true);
        try {
            const data = await api.updatePaymentGateway(gatewayId, gatewayFormData);
            setGatewaysList(prev => prev.map(g => g.id === gatewayId ? data.data : g));
            setEditingGatewayId(null);
        } catch (err: any) {
            alert(err.response?.data?.message || 'Failed to update gateway settings.');
        } finally {
            setGatewaySaving(false);
        }
    };

    // Vehicle catalog handlers
    const handleCreateBrand = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!newBrandName.trim()) return;
        try {
            await api.createVehicleBrand({ name: newBrandName });
            setNewBrandName('');
            loadVehicleBrands();
        } catch (err: any) {
            alert(err.response?.data?.message || 'Failed to create brand.');
        }
    };

    const handleToggleBrand = async (brandId: number, isActive: boolean) => {
        try {
            await api.updateVehicleBrand(brandId, { is_active: !isActive });
            loadVehicleBrands();
        } catch (err) {
            alert('Failed to update brand.');
        }
    };

    const handleExpandBrand = async (brandId: number) => {
        if (expandedBrandId === brandId) {
            setExpandedBrandId(null);
            return;
        }
        setExpandedBrandId(brandId);
        if (!brandModels[brandId]) {
            await loadBrandModels(brandId);
        }
    };

    const handleCreateModel = async (brandId: number) => {
        const form = newModelForms[brandId];
        if (!form?.name?.trim()) return;
        try {
            await api.createVehicleModel(brandId, { name: form.name, body_type: form.body_type });
            setNewModelForms(prev => ({ ...prev, [brandId]: { name: '', body_type: '' } }));
            await loadBrandModels(brandId);
        } catch (err: any) {
            alert(err.response?.data?.message || 'Failed to add model.');
        }
    };

    const handleToggleModel = async (modelId: number, brandId: number, isActive: boolean) => {
        try {
            await api.updateVehicleModel(modelId, { is_active: !isActive });
            await loadBrandModels(brandId);
        } catch (err) {
            alert('Failed to update model.');
        }
    };

    const metrics = dashboardData?.metrics;

    // Provider display info
    const gatewayInfo: Record<string, { label: string; description: string; color: string }> = {
        chapa:     { label: 'Chapa', description: 'Ethiopian multi-channel payments (Telebirr, CBE, Cards)', color: 'emerald' },
        telebirr:  { label: 'Telebirr', description: "Ethio Telecom's mobile money wallet", color: 'blue' },
        cbe:       { label: 'CBE Birr', description: 'Commercial Bank of Ethiopia mobile banking', color: 'indigo' },
        ebirr:     { label: 'eBirr', description: 'eBirr mobile wallet payments', color: 'violet' },
        santimpay: { label: 'SantimPay', description: 'Ethiopian payment gateway aggregator', color: 'amber' },
        paypal:    { label: 'PayPal', description: 'International PayPal payments (USD)', color: 'sky' },
    };

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
                        Platform listings moderation, payment gateways, vehicle catalog, users & categories.
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
                    { key: 'gateways', label: 'Payment Gateways' },
                    { key: 'vehicle-catalog', label: 'Vehicle Catalog' },
                ].map(t => (
                    <button
                        key={t.key}
                        onClick={() => handleTabSwitch(t.key as AdminSubTab)}
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

            {/* TAB: PAYMENT GATEWAYS */}
            {subTab === 'gateways' && (
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="font-extrabold text-slate-900 text-base">Payment Gateway Configuration</h3>
                            <p className="text-xs text-slate-500 mt-0.5">Configure Ethiopian and international payment providers. Credentials are stored securely and never exposed.</p>
                        </div>
                        <button onClick={loadGateways} className="p-2 border border-slate-200 rounded-xl text-slate-500 hover:bg-slate-50">
                            <RefreshCw className="w-4 h-4" />
                        </button>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        {gatewaysList.map(gw => {
                            const info = gatewayInfo[gw.code] || { label: gw.name, description: 'Payment provider', color: 'slate' };
                            const isEditing = editingGatewayId === gw.id;

                            return (
                                <div key={gw.id} className={`bg-white rounded-2xl border shadow-xs overflow-hidden ${gw.is_active ? 'border-slate-200' : 'border-slate-100 opacity-70'}`}>
                                    <div className="p-4 flex items-start justify-between gap-3">
                                        <div className="flex items-start gap-3 flex-1 min-w-0">
                                            <div className={`w-10 h-10 rounded-xl bg-${info.color}-100 text-${info.color}-700 flex items-center justify-center shrink-0`}>
                                                <CreditCard className="w-5 h-5" />
                                            </div>
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-2 flex-wrap">
                                                    <h4 className="font-extrabold text-slate-900 text-sm">{info.label}</h4>
                                                    <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${gw.is_test_mode ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'}`}>
                                                        {gw.is_test_mode ? 'SANDBOX' : 'LIVE'}
                                                    </span>
                                                    {gw.is_active && (
                                                        <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                                                            Active
                                                        </span>
                                                    )}
                                                </div>
                                                <p className="text-[11px] text-slate-500 mt-0.5">{info.description}</p>
                                                <div className="flex gap-3 mt-1.5 text-[11px] text-slate-400">
                                                    <span>API Key: {gw.has_api_key ? '✓ Set' : '⚠ Not set'}</span>
                                                    <span>Secret: {gw.has_secret ? '✓ Set' : '⚠ Not set'}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2 shrink-0">
                                            <button
                                                onClick={() => handleToggleGateway(gw.id)}
                                                className={`p-1.5 rounded-lg text-xs font-bold transition ${gw.is_active ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100' : 'text-slate-400 bg-slate-50 hover:bg-slate-100'}`}
                                                title={gw.is_active ? 'Disable gateway' : 'Enable gateway'}
                                            >
                                                {gw.is_active ? <ToggleRight className="w-5 h-5" /> : <ToggleLeft className="w-5 h-5" />}
                                            </button>
                                            <button
                                                onClick={() => isEditing ? setEditingGatewayId(null) : handleEditGateway(gw)}
                                                className="p-1.5 rounded-lg text-slate-500 bg-slate-50 hover:bg-slate-100 transition"
                                            >
                                                <Settings className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>

                                    {/* Edit Form */}
                                    {isEditing && (
                                        <div className="border-t border-slate-100 p-4 bg-slate-50 space-y-3 text-xs">
                                            <div className="flex items-center gap-3 mb-2">
                                                <label className="flex items-center gap-2 cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={gatewayFormData.is_test_mode}
                                                        onChange={e => setGatewayFormData(p => ({ ...p, is_test_mode: e.target.checked }))}
                                                        className="w-4 h-4 rounded text-emerald-600"
                                                    />
                                                    <span className="font-semibold text-slate-700">Sandbox / Test Mode</span>
                                                </label>
                                            </div>
                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                <div>
                                                    <label className="block font-bold text-slate-600 mb-1">API Key</label>
                                                    <input
                                                        type="password"
                                                        placeholder="Enter new API key (leave blank to keep)"
                                                        value={gatewayFormData.api_key}
                                                        onChange={e => setGatewayFormData(p => ({ ...p, api_key: e.target.value }))}
                                                        className="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-mono"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block font-bold text-slate-600 mb-1">Secret Key</label>
                                                    <input
                                                        type="password"
                                                        placeholder="Enter new secret key (leave blank to keep)"
                                                        value={gatewayFormData.secret_key}
                                                        onChange={e => setGatewayFormData(p => ({ ...p, secret_key: e.target.value }))}
                                                        className="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-mono"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block font-bold text-slate-600 mb-1">Public Key (if applicable)</label>
                                                    <input
                                                        type="text"
                                                        placeholder="Public-facing key"
                                                        value={gatewayFormData.public_key}
                                                        onChange={e => setGatewayFormData(p => ({ ...p, public_key: e.target.value }))}
                                                        className="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-mono"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block font-bold text-slate-600 mb-1">Merchant / Account ID</label>
                                                    <input
                                                        type="text"
                                                        placeholder="Merchant ID or account number"
                                                        value={gatewayFormData.merchant_id}
                                                        onChange={e => setGatewayFormData(p => ({ ...p, merchant_id: e.target.value }))}
                                                        className="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs"
                                                    />
                                                </div>
                                                <div className="sm:col-span-2">
                                                    <label className="block font-bold text-slate-600 mb-1">Webhook / Callback URL</label>
                                                    <input
                                                        type="url"
                                                        placeholder="https://your-domain.com/api/webhooks/payment/..."
                                                        value={gatewayFormData.webhook_url}
                                                        onChange={e => setGatewayFormData(p => ({ ...p, webhook_url: e.target.value }))}
                                                        className="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-mono"
                                                    />
                                                </div>
                                            </div>
                                            <div className="flex justify-end gap-2 pt-1">
                                                <button
                                                    onClick={() => setEditingGatewayId(null)}
                                                    className="px-4 py-1.5 rounded-lg text-slate-600 hover:bg-slate-100 font-bold text-xs"
                                                >
                                                    Cancel
                                                </button>
                                                <button
                                                    onClick={() => handleSaveGateway(gw.id)}
                                                    disabled={gatewaySaving}
                                                    className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-1.5 rounded-lg text-xs disabled:opacity-50"
                                                >
                                                    {gatewaySaving ? 'Saving...' : 'Save Settings'}
                                                </button>
                                            </div>
                                        </div>
                                    )}

                                    {/* Webhook info */}
                                    {!isEditing && gw.webhook_url && (
                                        <div className="border-t border-slate-100 px-4 py-2 text-[11px] text-slate-400 font-mono truncate">
                                            Webhook: {gw.webhook_url}
                                        </div>
                                    )}
                                </div>
                            );
                        })}

                        {gatewaysList.length === 0 && (
                            <div className="col-span-2 bg-white p-12 rounded-2xl border border-slate-200 text-center text-xs text-slate-400">
                                No payment gateways configured. They are seeded automatically — try refreshing.
                            </div>
                        )}
                    </div>

                    <div className="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-xs text-amber-800 flex items-start gap-3">
                        <Shield className="w-4 h-4 shrink-0 mt-0.5 text-amber-600" />
                        <div>
                            <p className="font-bold">Security Note</p>
                            <p className="mt-0.5 font-normal">API keys and secret keys are stored encrypted in the database and masked in this UI. They are never transmitted back to the browser in plaintext.</p>
                        </div>
                    </div>
                </div>
            )}

            {/* TAB: VEHICLE CATALOG */}
            {subTab === 'vehicle-catalog' && (
                <div className="space-y-5">
                    <div>
                        <h3 className="font-extrabold text-slate-900 text-base">Vehicle Brand & Model Catalog</h3>
                        <p className="text-xs text-slate-500 mt-0.5">Manage the searchable brand/model dropdowns used in the vehicle listing form.</p>
                    </div>

                    {/* Add Brand Form */}
                    <form onSubmit={handleCreateBrand} className="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex gap-3 items-end">
                        <div className="flex-1">
                            <label className="block text-xs font-bold text-slate-700 mb-1">New Brand Name</label>
                            <input
                                type="text"
                                required
                                value={newBrandName}
                                onChange={e => setNewBrandName(e.target.value)}
                                placeholder="e.g. Haval"
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                            />
                        </div>
                        <button type="submit" className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs shrink-0">
                            <Plus className="w-3.5 h-3.5 inline -mt-0.5 mr-1" />
                            Add Brand
                        </button>
                    </form>

                    {/* Brand List */}
                    {catalogLoading ? (
                        <div className="text-center py-8 text-xs text-slate-400">Loading vehicle catalog...</div>
                    ) : (
                        <div className="space-y-2">
                            {vehicleBrands.map(brand => (
                                <div key={brand.id} className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                                    {/* Brand Row */}
                                    <div className="flex items-center gap-3 p-4">
                                        <button
                                            onClick={() => handleExpandBrand(brand.id)}
                                            className="flex items-center gap-2 flex-1 text-left"
                                        >
                                            <div className="w-7 h-7 bg-slate-100 rounded-lg flex items-center justify-center">
                                                <Car className="w-3.5 h-3.5 text-slate-500" />
                                            </div>
                                            <span className="font-bold text-slate-900 text-sm">{brand.name}</span>
                                            <span className="text-[11px] text-slate-400">({brand.models_count ?? '?'} models)</span>
                                            {expandedBrandId === brand.id
                                                ? <ChevronDown className="w-4 h-4 text-slate-400 ml-auto" />
                                                : <ChevronRight className="w-4 h-4 text-slate-400 ml-auto" />
                                            }
                                        </button>
                                        <button
                                            onClick={() => handleToggleBrand(brand.id, brand.is_active)}
                                            className={`text-[10px] font-bold px-2.5 py-1 rounded-lg ${brand.is_active ? 'bg-emerald-100 text-emerald-700 hover:bg-rose-100 hover:text-rose-700' : 'bg-rose-100 text-rose-700 hover:bg-emerald-100 hover:text-emerald-700'}`}
                                        >
                                            {brand.is_active ? 'Active' : 'Inactive'}
                                        </button>
                                    </div>

                                    {/* Models Panel */}
                                    {expandedBrandId === brand.id && (
                                        <div className="border-t border-slate-100 bg-slate-50/50">
                                            {/* Model List */}
                                            <div className="divide-y divide-slate-100">
                                                {(brandModels[brand.id] || []).map(model => (
                                                    <div key={model.id} className="flex items-center justify-between px-6 py-2.5 text-xs">
                                                        <div className="flex items-center gap-3">
                                                            <span className="font-semibold text-slate-800">{model.name}</span>
                                                            {model.body_type && (
                                                                <span className="text-[10px] bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-medium">
                                                                    {model.body_type}
                                                                </span>
                                                            )}
                                                        </div>
                                                        <button
                                                            onClick={() => handleToggleModel(model.id, brand.id, model.is_active)}
                                                            className={`text-[10px] font-bold px-2 py-0.5 rounded ${model.is_active ? 'text-emerald-700 bg-emerald-50 hover:bg-rose-50 hover:text-rose-700' : 'text-rose-700 bg-rose-50 hover:bg-emerald-50 hover:text-emerald-700'}`}
                                                        >
                                                            {model.is_active ? 'Active' : 'Inactive'}
                                                        </button>
                                                    </div>
                                                ))}
                                                {(brandModels[brand.id] || []).length === 0 && (
                                                    <div className="px-6 py-3 text-xs text-slate-400">No models yet. Add one below.</div>
                                                )}
                                            </div>

                                            {/* Add Model Form */}
                                            <div className="border-t border-slate-200 p-4 flex gap-2">
                                                <input
                                                    type="text"
                                                    placeholder="Model name e.g. Corolla"
                                                    value={newModelForms[brand.id]?.name || ''}
                                                    onChange={e => setNewModelForms(prev => ({ ...prev, [brand.id]: { ...prev[brand.id], name: e.target.value } }))}
                                                    className="flex-1 bg-white border border-slate-200 rounded-lg px-3 py-1.5 text-xs"
                                                />
                                                <select
                                                    value={newModelForms[brand.id]?.body_type || ''}
                                                    onChange={e => setNewModelForms(prev => ({ ...prev, [brand.id]: { ...prev[brand.id], body_type: e.target.value } }))}
                                                    className="bg-white border border-slate-200 rounded-lg px-2 py-1.5 text-xs"
                                                >
                                                    <option value="">Body type</option>
                                                    {['Sedan', 'SUV', 'Pickup', 'Hatchback', 'Van', 'Minivan', 'Coupe', 'Wagon', 'Truck', 'Bus', 'Convertible'].map(bt => (
                                                        <option key={bt} value={bt}>{bt}</option>
                                                    ))}
                                                </select>
                                                <button
                                                    onClick={() => handleCreateModel(brand.id)}
                                                    className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs"
                                                >
                                                    + Add
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
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
