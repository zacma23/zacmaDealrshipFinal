import React, { useState } from 'react';
import { User } from '../types/marketplace';
import { api } from '../services/api';
import { 
    User as UserIcon, Building, ShieldCheck, Camera, Sparkles, 
    ExternalLink, Layers, CheckCircle2, AlertCircle, Loader2,
    Mail, Phone, MapPin, Globe, FileText, Lock
} from 'lucide-react';

interface ProfileSettingsViewProps {
    user: User;
    onUserUpdated: (updatedUser: User) => void;
    onNavigateTab: (tab: string, extra?: { username?: string }) => void;
}

export const ProfileSettingsView: React.FC<ProfileSettingsViewProps> = ({
    user,
    onUserUpdated,
    onNavigateTab,
}) => {
    const [name, setName] = useState(user.name || '');
    const [username, setUsername] = useState(user.username || '');
    const [phone, setPhone] = useState(user.phone || '');
    const [city, setCity] = useState(user.profile?.city || 'Addis Ababa');
    const [bio, setBio] = useState(user.profile?.bio || '');

    // Dealership details
    const [accountType, setAccountType] = useState<'individual' | 'business' | 'dealer'>(
        user.profile?.account_type || 'individual'
    );
    const [businessName, setBusinessName] = useState(user.profile?.business_name || '');
    const [licenseNumber, setLicenseNumber] = useState(user.profile?.license_number || '');
    const [tinNumber, setTinNumber] = useState(user.profile?.tin_number || '');
    const [address, setAddress] = useState(user.profile?.address || '');
    const [website, setWebsite] = useState(user.profile?.website || '');

    const [avatarFile, setAvatarFile] = useState<File | null>(null);
    const [avatarPreview, setAvatarPreview] = useState<string | null>(null);

    const [saving, setSaving] = useState(false);
    const [statusMessage, setStatusMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            const file = e.target.files[0];
            setAvatarFile(file);
            setAvatarPreview(URL.createObjectURL(file));
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);
        setStatusMessage(null);

        try {
            const formData = new FormData();
            formData.append('name', name);
            if (username) formData.append('username', username);
            if (phone) formData.append('phone', phone);
            if (city) formData.append('city', city);
            if (bio) formData.append('bio', bio);
            formData.append('account_type', accountType);
            if (businessName) formData.append('business_name', businessName);
            if (licenseNumber) formData.append('license_number', licenseNumber);
            if (tinNumber) formData.append('tin_number', tinNumber);
            if (address) formData.append('address', address);
            if (website) formData.append('website', website);
            if (avatarFile) {
                formData.append('photo', avatarFile);
            }

            const res = await api.updateProfile(formData);
            const freshUser = await api.getMe();
            onUserUpdated(freshUser);

            setStatusMessage({
                type: 'success',
                text: res.message || 'Profile settings saved successfully!',
            });
        } catch (err: any) {
            console.error('Failed to update profile', err);
            const msg = err.response?.data?.message
                || (err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join(' ') : null)
                || 'Failed to update profile. Please check your inputs.';
            setStatusMessage({ type: 'error', text: msg });
        } finally {
            setSaving(false);
        }
    };

    const quotaUsed = user.quota?.used ?? 0;
    const quotaLimit = user.quota?.limit ?? 20;
    const quotaRemaining = user.quota?.remaining ?? Math.max(0, quotaLimit - quotaUsed);
    const percentUsed = Math.min(100, Math.round((quotaUsed / (quotaLimit || 1)) * 100));

    return (
        <div className="max-w-4xl mx-auto space-y-8 pb-16">
            {/* Header */}
            <div className="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div className="flex items-center gap-4">
                    <div className="relative group">
                        <img
                            src={avatarPreview || user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=10B981&color=FFFFFF`}
                            alt={user.name}
                            className="w-20 h-20 rounded-2xl object-cover ring-2 ring-emerald-500/30 shadow-md"
                        />
                        <label className="absolute -bottom-2 -right-2 bg-slate-900 hover:bg-emerald-600 text-white p-2 rounded-xl shadow-md cursor-pointer transition">
                            <Camera className="w-3.5 h-3.5" />
                            <input
                                type="file"
                                accept="image/*"
                                className="hidden"
                                onChange={handleFileChange}
                            />
                        </label>
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-black text-slate-900">{user.name}</h1>
                            {user.profile?.is_verified && (
                                <span className="inline-flex items-center gap-1 text-[11px] font-bold bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full">
                                    <ShieldCheck className="w-3 h-3 text-emerald-600" />
                                    Verified
                                </span>
                            )}
                        </div>
                        <p className="text-xs text-slate-500 mt-0.5">
                            @{user.username || 'user'} • {user.email}
                        </p>
                        <div className="flex items-center gap-2 mt-2">
                            <span className="text-[11px] font-semibold bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded-lg">
                                {accountType === 'dealer' ? '🚗 Certified Dealer' : accountType === 'business' ? '🏢 Real Estate / Business' : '👤 Individual Seller'}
                            </span>
                            <span className="text-[11px] font-semibold bg-emerald-50 text-emerald-700 px-2.5 py-0.5 rounded-lg">
                                {user.quota?.plan_name || 'Basic'} Plan
                            </span>
                        </div>
                    </div>
                </div>

                {/* Quick actions */}
                <div className="flex flex-wrap items-center gap-2.5">
                    {user.username && (
                        <button
                            type="button"
                            onClick={() => onNavigateTab('public-profile', { username: user.username })}
                            className="text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 px-3.5 py-2 rounded-xl transition flex items-center gap-1.5"
                        >
                            <ExternalLink className="w-3.5 h-3.5" />
                            <span>View Public Profile</span>
                        </button>
                    )}
                    <button
                        type="button"
                        onClick={() => onNavigateTab('my-listings')}
                        className="text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 px-3.5 py-2 rounded-xl transition flex items-center gap-1.5"
                    >
                        <Layers className="w-3.5 h-3.5" />
                        <span>My Listings ({quotaUsed})</span>
                    </button>
                </div>
            </div>

            {/* Quota Banner */}
            <div className="bg-gradient-to-r from-emerald-900 to-slate-900 text-white rounded-3xl p-6 shadow-md flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div className="space-y-1 max-w-md">
                    <div className="flex items-center gap-2">
                        <Sparkles className="w-4 h-4 text-emerald-400" />
                        <span className="text-xs font-bold uppercase tracking-wider text-emerald-300">
                            Listing Capacity & Quota
                        </span>
                    </div>
                    <h3 className="text-lg font-black">
                        {quotaUsed} of {quotaLimit} Listings Active
                    </h3>
                    <div className="w-full bg-slate-800 rounded-full h-2.5 mt-2 overflow-hidden">
                        <div
                            className="bg-emerald-400 h-2.5 rounded-full transition-all duration-500"
                            style={{ width: `${percentUsed}%` }}
                        />
                    </div>
                    <p className="text-[11px] text-slate-300 pt-1">
                        You have <strong className="text-white font-bold">{quotaRemaining} listings remaining</strong> on your {user.quota?.plan_name || 'Basic'} subscription.
                    </p>
                </div>

                <button
                    type="button"
                    onClick={() => onNavigateTab('pricing')}
                    className="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs px-4 py-2.5 rounded-xl shadow-lg transition flex items-center gap-1.5"
                >
                    <Sparkles className="w-3.5 h-3.5" />
                    <span>Upgrade Plan & Quota</span>
                </button>
            </div>

            {/* Status alerts */}
            {statusMessage && (
                <div
                    className={`rounded-2xl p-4 text-xs font-semibold flex items-center gap-2.5 shadow-xs ${
                        statusMessage.type === 'success'
                            ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                            : 'bg-rose-50 text-rose-800 border border-rose-200'
                    }`}
                >
                    {statusMessage.type === 'success' ? (
                        <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                    ) : (
                        <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
                    )}
                    <span>{statusMessage.text}</span>
                </div>
            )}

            {/* Profile Form */}
            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Personal Information */}
                <div className="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
                    <div className="border-b border-slate-100 pb-4">
                        <h2 className="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <UserIcon className="w-4 h-4 text-emerald-600" />
                            <span>Personal Information</span>
                        </h2>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Your public dealer and seller identity displayed across vehicle and property listings.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                Full Name <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                required
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                className="w-full text-xs font-semibold px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                placeholder="e.g. Dawit Haile"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                Public Username
                            </label>
                            <div className="relative">
                                <span className="absolute left-3.5 top-2.5 text-xs text-slate-400 font-bold">@</span>
                                <input
                                    type="text"
                                    value={username}
                                    onChange={(e) => setUsername(e.target.value.toLowerCase().replace(/[^a-z0-9_-]/g, ''))}
                                    className="w-full text-xs font-semibold pl-8 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                    placeholder="dawit_motors"
                                />
                            </div>
                            <span className="text-[10px] text-slate-400 mt-1 block">
                                Profile URL: zacma.et/profile/{username || 'username'}
                            </span>
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                Phone Number (Calls & Telegram)
                            </label>
                            <div className="relative">
                                <Phone className="absolute left-3.5 top-2.5 w-3.5 h-3.5 text-slate-400" />
                                <input
                                    type="tel"
                                    value={phone}
                                    onChange={(e) => setPhone(e.target.value)}
                                    className="w-full text-xs font-semibold pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                    placeholder="+251 911 234567"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                City / Region
                            </label>
                            <div className="relative">
                                <MapPin className="absolute left-3.5 top-2.5 w-3.5 h-3.5 text-slate-400" />
                                <input
                                    type="text"
                                    value={city}
                                    onChange={(e) => setCity(e.target.value)}
                                    className="w-full text-xs font-semibold pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                    placeholder="Addis Ababa, Bole"
                                />
                            </div>
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-slate-700 mb-1.5">
                            About You / Dealership Bio
                        </label>
                        <textarea
                            rows={3}
                            value={bio}
                            onChange={(e) => setBio(e.target.value)}
                            className="w-full text-xs font-medium p-3.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            placeholder="Tell buyers about your dealership, inventory specialization (e.g. Japanese vehicles, luxury villas in CMC, or rental apartments)..."
                        />
                    </div>
                </div>

                {/* Dealership & Business Verification */}
                <div className="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
                    <div className="border-b border-slate-100 pb-4">
                        <h2 className="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <Building className="w-4 h-4 text-emerald-600" />
                            <span>Dealership & Business Verification</span>
                        </h2>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Provide your registered enterprise info for buyer trust and verified badge eligibility.
                        </p>
                    </div>

                    {/* Account Type Selector */}
                    <div>
                        <label className="block text-xs font-bold text-slate-700 mb-2">
                            Seller Type
                        </label>
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            {[
                                { key: 'individual', label: 'Individual Seller', desc: 'Private owner or occasional seller' },
                                { key: 'dealer', label: 'Automotive Dealer', desc: 'Licensed car showroom or dealership' },
                                { key: 'business', label: 'Real Estate / Agency', desc: 'Brokerage, developer, or firm' },
                            ].map((type) => (
                                <button
                                    key={type.key}
                                    type="button"
                                    onClick={() => setAccountType(type.key as any)}
                                    className={`p-3 rounded-2xl border text-left transition ${
                                        accountType === type.key
                                            ? 'border-emerald-600 bg-emerald-50/50 ring-2 ring-emerald-500/20'
                                            : 'border-slate-200 hover:border-slate-300 bg-slate-50/50'
                                    }`}
                                >
                                    <div className="text-xs font-extrabold text-slate-900">{type.label}</div>
                                    <div className="text-[10px] text-slate-500 mt-0.5">{type.desc}</div>
                                </button>
                            ))}
                        </div>
                    </div>

                    {accountType !== 'individual' && (
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                    Business / Dealership Name
                                </label>
                                <input
                                    type="text"
                                    value={businessName}
                                    onChange={(e) => setBusinessName(e.target.value)}
                                    className="w-full text-xs font-semibold px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                    placeholder="e.g. Apex Auto Import PLC"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                    Trade License #
                                </label>
                                <input
                                    type="text"
                                    value={licenseNumber}
                                    onChange={(e) => setLicenseNumber(e.target.value)}
                                    className="w-full text-xs font-semibold px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                    placeholder="e.g. MOT/AA/2024/0981"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                    Tax Identification Number (TIN)
                                </label>
                                <input
                                    type="text"
                                    value={tinNumber}
                                    onChange={(e) => setTinNumber(e.target.value)}
                                    className="w-full text-xs font-semibold px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                    placeholder="e.g. 0081923411"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                    Official Website
                                </label>
                                <div className="relative">
                                    <Globe className="absolute left-3.5 top-2.5 w-3.5 h-3.5 text-slate-400" />
                                    <input
                                        type="url"
                                        value={website}
                                        onChange={(e) => setWebsite(e.target.value)}
                                        className="w-full text-xs font-semibold pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                        placeholder="https://apexmotors.et"
                                    />
                                </div>
                            </div>

                            <div className="sm:col-span-2">
                                <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                    Showroom / Office Physical Address
                                </label>
                                <input
                                    type="text"
                                    value={address}
                                    onChange={(e) => setAddress(e.target.value)}
                                    className="w-full text-xs font-semibold px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                    placeholder="e.g. Bole Medhanialem, Morning Star Mall 4th Floor, Addis Ababa"
                                />
                            </div>
                        </div>
                    )}
                </div>

                {/* Submit Action */}
                <div className="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="button"
                        onClick={() => onNavigateTab('browse')}
                        className="text-xs font-bold text-slate-600 hover:text-slate-800 px-4 py-2.5 rounded-xl transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        disabled={saving}
                        className="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black px-6 py-2.5 rounded-xl shadow-md shadow-emerald-600/30 transition flex items-center gap-2 disabled:opacity-60"
                    >
                        {saving ? (
                            <>
                                <Loader2 className="w-4 h-4 animate-spin" />
                                <span>Saving Changes...</span>
                            </>
                        ) : (
                            <>
                                <CheckCircle2 className="w-4 h-4" />
                                <span>Save Profile Settings</span>
                            </>
                        )}
                    </button>
                </div>
            </form>
        </div>
    );
};
