import React, { useState } from 'react';
import { User } from '../types/marketplace';
import { 
    Car, Home, Building2, Heart, Inbox, PlusCircle, 
    ShieldAlert, UserCheck, LogOut, User as UserIcon, 
    Layers, Sparkles, Menu, X, ChevronDown, CheckCircle2,
    MessageSquare, HelpCircle
} from 'lucide-react';

interface NavbarProps {
    user: User | null;
    activeTab: string;
    onTabChange: (tab: string) => void;
    onOpenPostListing: () => void;
    onOpenAuth: (mode: 'login' | 'register') => void;
    onLogout: () => void;
    crmNewCount?: number;
}

export const Navbar: React.FC<NavbarProps> = ({
    user,
    activeTab,
    onTabChange,
    onOpenPostListing,
    onOpenAuth,
    onLogout,
    crmNewCount = 0,
}) => {
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [userDropdownOpen, setUserDropdownOpen] = useState(false);

    return (
        <header className="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200/80 shadow-xs">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between h-16">
                    {/* Brand */}
                    <div className="flex items-center gap-6">
                        <button 
                            onClick={() => onTabChange('browse')}
                            className="flex items-center gap-2.5 text-left group focus:outline-none"
                        >
                            <div className="h-9 w-9 bg-gradient-to-tr from-emerald-600 to-teal-500 rounded-xl flex items-center justify-center text-white font-black text-lg shadow-md shadow-emerald-600/20 group-hover:scale-105 transition">
                                Z
                            </div>
                            <div>
                                <div className="flex items-center gap-1.5">
                                    <span className="font-extrabold text-lg tracking-tight text-slate-900">Zacma</span>
                                    <span className="text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 px-1.5 py-0.2 rounded">ET</span>
                                </div>
                                <p className="text-[10px] text-slate-500 font-medium leading-none">Marketplace & CRM</p>
                            </div>
                        </button>

                        {/* Navigation Links */}
                        <nav className="hidden md:flex items-center gap-1">
                            <button
                                onClick={() => onTabChange('browse')}
                                className={`px-3 py-1.5 rounded-lg text-sm font-semibold transition ${
                                    activeTab === 'browse'
                                        ? 'bg-slate-100 text-emerald-700'
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                }`}
                            >
                                Browse Market
                            </button>

                            {user && (
                                <>
                                    <button
                                        onClick={() => onTabChange('my-listings')}
                                        className={`px-3 py-1.5 rounded-lg text-sm font-semibold transition ${
                                            activeTab === 'my-listings'
                                                ? 'bg-slate-100 text-emerald-700'
                                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                        }`}
                                    >
                                        My Listings
                                    </button>

                                    <button
                                        onClick={() => onTabChange('crm-leads')}
                                        className={`px-3 py-1.5 rounded-lg text-sm font-semibold flex items-center gap-1.5 transition ${
                                            activeTab === 'crm-leads'
                                                ? 'bg-slate-100 text-emerald-700'
                                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                        }`}
                                    >
                                        <span>CRM Leads</span>
                                        {crmNewCount > 0 && (
                                            <span className="bg-rose-500 text-white text-[10px] font-bold px-1.5 py-0.2 rounded-full animate-pulse">
                                                {crmNewCount}
                                            </span>
                                        )}
                                    </button>

                                    <button
                                        onClick={() => onTabChange('favorites')}
                                        className={`px-3 py-1.5 rounded-lg text-sm font-semibold flex items-center gap-1 transition ${
                                            activeTab === 'favorites'
                                                ? 'bg-slate-100 text-emerald-700'
                                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                        }`}
                                    >
                                        <Heart className="w-3.5 h-3.5" />
                                        <span>Saved</span>
                                    </button>

                                    <button
                                        onClick={() => onTabChange('messages')}
                                        className={`px-3 py-1.5 rounded-lg text-sm font-semibold flex items-center gap-1 transition ${
                                            activeTab === 'messages'
                                                ? 'bg-slate-100 text-emerald-700'
                                                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                        }`}
                                    >
                                        <MessageSquare className="w-3.5 h-3.5" />
                                        <span>Messages</span>
                                    </button>
                                </>
                            )}

                            <button
                                onClick={() => onTabChange('requirements')}
                                className={`px-3 py-1.5 rounded-lg text-sm font-semibold flex items-center gap-1 transition ${
                                    activeTab === 'requirements'
                                        ? 'bg-slate-100 text-emerald-700'
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                }`}
                            >
                                <HelpCircle className="w-3.5 h-3.5" />
                                <span>Buyer Needs</span>
                            </button>

                            <button
                                onClick={() => onTabChange('pricing')}
                                className={`px-3 py-1.5 rounded-lg text-sm font-semibold transition ${
                                    activeTab === 'pricing'
                                        ? 'bg-slate-100 text-emerald-700'
                                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'
                                }`}
                            >
                                Plans & Pricing
                            </button>

                            {user?.is_super_admin && (
                                <button
                                    onClick={() => onTabChange('admin')}
                                    className={`px-3 py-1.5 rounded-lg text-sm font-bold flex items-center gap-1 transition ${
                                        activeTab === 'admin'
                                            ? 'bg-rose-100 text-rose-800'
                                            : 'text-rose-600 hover:bg-rose-50'
                                    }`}
                                >
                                    <ShieldAlert className="w-3.5 h-3.5" />
                                    <span>Admin Panel</span>
                                </button>
                            )}
                        </nav>
                    </div>

                    {/* Actions & User menu */}
                    <div className="flex items-center gap-3">
                        {/* Add Listing Button */}
                        <button
                            onClick={onOpenPostListing}
                            className="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-3.5 py-2 rounded-xl flex items-center gap-1.5 shadow-sm shadow-emerald-600/30 transition hover:shadow-md"
                        >
                            <PlusCircle className="w-4 h-4" />
                            <span className="hidden sm:inline">Add Listing</span>
                        </button>

                        {user ? (
                            <div className="relative">
                                <button
                                    onClick={() => setUserDropdownOpen(!userDropdownOpen)}
                                    className="flex items-center gap-2 p-1 pl-2 rounded-xl hover:bg-slate-100 transition border border-slate-200/60"
                                >
                                    <img
                                        src={user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=10B981&color=FFFFFF`}
                                        alt={user.name}
                                        className="w-7 h-7 rounded-lg object-cover ring-1 ring-emerald-500/30"
                                    />
                                    <div className="hidden lg:block text-left pr-1">
                                        <div className="text-xs font-bold text-slate-800 truncate max-w-[120px]">{user.name}</div>
                                        <div className="text-[10px] text-emerald-600 font-semibold leading-none">
                                            {user.quota ? `${user.quota.used}/${user.quota.limit} listings` : 'Active'}
                                        </div>
                                    </div>
                                    <ChevronDown className="w-3.5 h-3.5 text-slate-400" />
                                </button>

                                {userDropdownOpen && (
                                    <div className="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200/80 py-1 z-50 animate-in fade-in zoom-in-95 duration-100">
                                        <div className="px-4 py-2.5 border-b border-slate-100">
                                            <p className="text-xs text-slate-400">Signed in as</p>
                                            <p className="text-sm font-bold text-slate-900 truncate">{user.name}</p>
                                            <p className="text-xs text-slate-500 truncate">@{user.username || 'user'}</p>
                                            <div className="mt-1.5 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                                <span className="text-slate-500">Listing Quota:</span>
                                                <span className="font-bold text-emerald-700">
                                                    {user.quota?.used ?? 0} / {user.quota?.limit ?? 20}
                                                </span>
                                            </div>
                                        </div>

                                        <button
                                            onClick={() => {
                                                onTabChange('my-listings');
                                                setUserDropdownOpen(false);
                                            }}
                                            className="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2"
                                        >
                                            <Layers className="w-4 h-4 text-slate-400" />
                                            <span>My Listings ({user.quota?.used ?? 0})</span>
                                        </button>

                                        <button
                                            onClick={() => {
                                                onTabChange('crm-leads');
                                                setUserDropdownOpen(false);
                                            }}
                                            className="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2"
                                        >
                                            <Inbox className="w-4 h-4 text-slate-400" />
                                            <span>Inbound CRM Leads</span>
                                        </button>

                                        <button
                                            onClick={() => {
                                                onTabChange('profile');
                                                setUserDropdownOpen(false);
                                            }}
                                            className="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2"
                                        >
                                            <UserIcon className="w-4 h-4 text-slate-400" />
                                            <span>Profile Settings</span>
                                        </button>

                                        <button
                                            onClick={() => {
                                                onTabChange('pricing');
                                                setUserDropdownOpen(false);
                                            }}
                                            className="w-full text-left px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2"
                                        >
                                            <Sparkles className="w-4 h-4 text-amber-500" />
                                            <span>Upgrade Plan & Quota</span>
                                        </button>

                                        {user.is_super_admin && (
                                            <button
                                                onClick={() => {
                                                    onTabChange('admin');
                                                    setUserDropdownOpen(false);
                                                }}
                                                className="w-full text-left px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 flex items-center gap-2 border-t border-slate-100"
                                            >
                                                <ShieldAlert className="w-4 h-4 text-rose-500" />
                                                <span>Admin Command Center</span>
                                            </button>
                                        )}

                                        <div className="border-t border-slate-100 mt-1">
                                            <button
                                                onClick={() => {
                                                    setUserDropdownOpen(false);
                                                    onLogout();
                                                }}
                                                className="w-full text-left px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 flex items-center gap-2"
                                            >
                                                <LogOut className="w-4 h-4" />
                                                <span>Sign Out</span>
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="flex items-center gap-2">
                                <button
                                    onClick={() => onOpenAuth('login')}
                                    className="text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition"
                                >
                                    Log In
                                </button>
                                <button
                                    onClick={() => onOpenAuth('register')}
                                    className="text-xs sm:text-sm font-bold bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-1.5 rounded-lg transition shadow-xs"
                                >
                                    Register
                                </button>
                            </div>
                        )}

                        {/* Mobile menu toggle */}
                        <button
                            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                            className="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100"
                        >
                            {mobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
                        </button>
                    </div>
                </div>

                {/* Mobile Menu */}
                {mobileMenuOpen && (
                    <div className="md:hidden py-3 border-t border-slate-100 flex flex-col gap-1.5">
                        <button
                            onClick={() => { onTabChange('browse'); setMobileMenuOpen(false); }}
                            className="text-left px-3 py-2 text-sm font-semibold rounded-lg hover:bg-slate-100 text-slate-800"
                        >
                            Browse Market
                        </button>
                        {user && (
                            <>
                                <button
                                    onClick={() => { onTabChange('my-listings'); setMobileMenuOpen(false); }}
                                    className="text-left px-3 py-2 text-sm font-semibold rounded-lg hover:bg-slate-100 text-slate-800"
                                >
                                    My Listings
                                </button>
                                <button
                                    onClick={() => { onTabChange('crm-leads'); setMobileMenuOpen(false); }}
                                    className="text-left px-3 py-2 text-sm font-semibold rounded-lg hover:bg-slate-100 text-slate-800 flex items-center justify-between"
                                >
                                    <span>CRM Inbound Leads</span>
                                    {crmNewCount > 0 && (
                                        <span className="bg-rose-500 text-white text-xs px-2 py-0.5 rounded-full">{crmNewCount}</span>
                                    )}
                                </button>
                                <button
                                    onClick={() => { onTabChange('favorites'); setMobileMenuOpen(false); }}
                                    className="text-left px-3 py-2 text-sm font-semibold rounded-lg hover:bg-slate-100 text-slate-800"
                                >
                                    Saved Listings
                                </button>
                                <button
                                    onClick={() => { onTabChange('messages'); setMobileMenuOpen(false); }}
                                    className="text-left px-3 py-2 text-sm font-semibold rounded-lg hover:bg-slate-100 text-slate-800"
                                >
                                    Messages
                                </button>
                            </>
                        )}
                        <button
                            onClick={() => { onTabChange('requirements'); setMobileMenuOpen(false); }}
                            className="text-left px-3 py-2 text-sm font-semibold rounded-lg hover:bg-slate-100 text-slate-800"
                        >
                            Buyer Needs
                        </button>
                        <button
                            onClick={() => { onTabChange('pricing'); setMobileMenuOpen(false); }}
                            className="text-left px-3 py-2 text-sm font-semibold rounded-lg hover:bg-slate-100 text-slate-800"
                        >
                            Plans & Pricing
                        </button>
                        {user?.is_super_admin && (
                            <button
                                onClick={() => { onTabChange('admin'); setMobileMenuOpen(false); }}
                                className="text-left px-3 py-2 text-sm font-bold text-rose-600 rounded-lg hover:bg-rose-50"
                            >
                                Admin Panel
                            </button>
                        )}
                    </div>
                )}
            </div>
        </header>
    );
};

