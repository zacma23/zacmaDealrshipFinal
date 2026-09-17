import React, { useState, useEffect, Component, ErrorInfo, ReactNode } from 'react';
import { createRoot } from 'react-dom/client';
import { Listing, User } from './types/marketplace';
import { api } from './services/api';
import { Navbar } from './components/Navbar';

// ─── Global Error Boundary ────────────────────────────────────────────────────
// Prevents a render error in any child from unmounting the entire app shell.
interface EBState { hasError: boolean; error?: Error }
class ErrorBoundary extends Component<{ children: ReactNode }, EBState> {
    constructor(props: { children: ReactNode }) {
        super(props);
        this.state = { hasError: false };
    }
    static getDerivedStateFromError(error: Error): EBState {
        return { hasError: true, error };
    }
    componentDidCatch(error: Error, info: ErrorInfo) {
        console.error('[ErrorBoundary] Caught render error:', error, info);
    }
    render() {
        if (this.state.hasError) {
            return (
                <div style={{ padding: '32px', textAlign: 'center', color: '#64748b' }}>
                    <h2 style={{ color: '#0f172a', marginBottom: '8px' }}>Something went wrong</h2>
                    <p style={{ fontSize: '13px' }}>{this.state.error?.message}</p>
                    <button
                        style={{ marginTop: '16px', padding: '8px 20px', background: '#059669', color: '#fff', border: 'none', borderRadius: '8px', cursor: 'pointer' }}
                        onClick={() => { this.setState({ hasError: false }); window.location.href = '/browse'; }}
                    >
                        Return to Browse
                    </button>
                </div>
            );
        }
        return this.props.children;
    }
}
// ─────────────────────────────────────────────────────────────────────────────
import { MarketplaceBrowse } from './components/MarketplaceBrowse';
import { ListingDetailModal } from './components/ListingDetailModal';
import { AddListingModal } from './components/AddListingModal';
import { MyListingsView } from './components/MyListingsView';
import { CrmLeadsView } from './components/CrmLeadsView';
import { FavoritesView } from './components/FavoritesView';
import { SubscriptionView } from './components/SubscriptionView';
import { PublicProfileView } from './components/PublicProfileView';
import { ProfileSettingsView } from './components/ProfileSettingsView';
import { AdminDashboardView } from './components/AdminDashboardView';
import { AuthModal } from './components/AuthModal';
import { MessagesView } from './components/MessagesView';
import { BuyerRequirementsView } from './components/BuyerRequirementsView';
import { AiAssistantWidget } from './components/AiAssistantWidget';
import { Lock, ShieldAlert, Loader2 } from 'lucide-react';

declare global {
    interface Window {
        __INITIAL_USER__?: User | null;
        __APP_NAME__?: string;
    }
}

const getPathForTab = (tab: string, username?: string | null): string => {
    switch (tab) {
        case 'browse': return '/browse';
        case 'my-listings': return '/my-listings';
        case 'crm-leads': return '/crm-leads';
        case 'favorites': return '/favorites';
        case 'messages': return '/messages';
        case 'requirements': return '/requirements';
        case 'pricing': return '/pricing';
        case 'admin': return '/admin';
        case 'profile': return '/settings';
        case 'public-profile': return username ? `/profile/${username}` : '/browse';
        default: return '/';
    }
};

const resolveRoute = (): { tab: string; username: string | null; authModal: 'login' | 'register' | null; listingSlug: string | null } => {
    const path = window.location.pathname.toLowerCase();
    const search = new URLSearchParams(window.location.search);

    if (search.get('payment') === 'success') {
        return { tab: 'pricing', username: null, authModal: null, listingSlug: null };
    }
    if (search.get('auth') === 'login' || path === '/login') {
        return { tab: 'browse', username: null, authModal: 'login', listingSlug: null };
    }
    if (search.get('auth') === 'register' || path === '/register') {
        return { tab: 'browse', username: null, authModal: 'register', listingSlug: null };
    }

    if (path.startsWith('/listings/')) {
        const slug = window.location.pathname.replace(/^\/listings\//i, '').split('/')[0];
        if (slug) {
            return { tab: 'browse', username: null, authModal: null, listingSlug: decodeURIComponent(slug) };
        }
    }

    if (path.startsWith('/profile/')) {
        const u = window.location.pathname.replace(/^\/profile\//i, '').split('/')[0];
        if (u) {
            return { tab: 'public-profile', username: decodeURIComponent(u), authModal: null, listingSlug: null };
        }
    }
    if (path === '/profile' || path === '/settings' || path === '/account') {
        return { tab: 'profile', username: null, authModal: null, listingSlug: null };
    }
    if (path === '/my-listings') {
        return { tab: 'my-listings', username: null, authModal: null, listingSlug: null };
    }
    if (path === '/crm-leads' || path === '/leads') {
        return { tab: 'crm-leads', username: null, authModal: null, listingSlug: null };
    }
    if (path === '/favorites' || path === '/saved') {
        return { tab: 'favorites', username: null, authModal: null, listingSlug: null };
    }
    if (path === '/messages' || path.startsWith('/messages/')) {
        return { tab: 'messages', username: null, authModal: null, listingSlug: null };
    }
    if (path === '/requirements' || path === '/buyer-requirements') {
        return { tab: 'requirements', username: null, authModal: null, listingSlug: null };
    }
    if (path === '/pricing' || path === '/plans' || path === '/subscriptions') {
        return { tab: 'pricing', username: null, authModal: null, listingSlug: null };
    }
    if (path === '/admin') {
        return { tab: 'admin', username: null, authModal: null, listingSlug: null };
    }

    // /category/* and /browse/* sub-paths → resolve to browse tab
    if (path.startsWith('/category/') || path.startsWith('/browse/')) {
        return { tab: 'browse', username: null, authModal: null, listingSlug: null };
    }

    return { tab: 'browse', username: null, authModal: null, listingSlug: null };
};

export const App: React.FC = () => {
    const initialRoute = resolveRoute();
    const [user, setUser] = useState<User | null>(window.__INITIAL_USER__ || null);
    const [activeTab, setActiveTab] = useState<string>(initialRoute.tab);
    const [selectedListing, setSelectedListing] = useState<Listing | null>(null);
    const [viewingUsername, setViewingUsername] = useState<string | null>(initialRoute.username);
    const [chatRecipientId, setChatRecipientId] = useState<number | null>(null);
    const [chatListingId, setChatListingId] = useState<number | null>(null);

    const [postListingOpen, setPostListingOpen] = useState(false);
    const [authModalOpen, setAuthModalOpen] = useState<'login' | 'register' | null>(initialRoute.authModal);
    const [crmNewCount, setCrmNewCount] = useState(0);

    const [authLoading, setAuthLoading] = useState<boolean>(() => {
        return !window.__INITIAL_USER__ && !!(localStorage.getItem('zacma_auth_token') || window.__INITIAL_TOKEN__);
    });

    const handleSelectListing = (listing: Listing | null) => {
        setSelectedListing(listing);
        if (listing) {
            const targetPath = `/listings/${listing.slug}`;
            if (window.location.pathname !== targetPath) {
                window.history.pushState({ tab: activeTab, username: viewingUsername, listingSlug: listing.slug }, '', targetPath);
            }
        } else {
            const targetPath = getPathForTab(activeTab, viewingUsername);
            if (window.location.pathname !== targetPath) {
                window.history.pushState({ tab: activeTab, username: viewingUsername, listingSlug: null }, '', targetPath);
            }
        }
    };

    const navigateTo = (tab: string, extra: { username?: string | null; push?: boolean; listingSlug?: string | null } = {}) => {
        const { username = null, push = true, listingSlug = null } = extra;
        setActiveTab(tab);
        if (tab === 'public-profile') {
            setViewingUsername(username);
        } else {
            setViewingUsername(null);
        }

        if (push) {
            let targetPath = getPathForTab(tab, username);
            if (listingSlug) {
                targetPath = `/listings/${listingSlug}`;
            }
            if (window.location.pathname !== targetPath) {
                window.history.pushState({ tab, username, listingSlug }, '', targetPath);
            }
        }
    };

    // Store initial history state and load initial listing if URL is /listings/{slug}
    useEffect(() => {
        const route = resolveRoute();
        window.history.replaceState(
            { tab: route.tab, username: route.username, listingSlug: route.listingSlug },
            '',
            window.location.pathname + window.location.search
        );

        if (route.listingSlug) {
            api.getListing(route.listingSlug)
                .then(l => setSelectedListing(l))
                .catch(() => {});
        }
    }, []);

    // Listen to browser Back and Forward navigation (popstate)
    useEffect(() => {
        const handlePopState = (e: PopStateEvent) => {
            const route = resolveRoute();
            setActiveTab(route.tab);
            setViewingUsername(route.username);
            if (route.listingSlug) {
                api.getListing(route.listingSlug)
                    .then(l => setSelectedListing(l))
                    .catch(() => setSelectedListing(null));
            } else {
                setSelectedListing(null);
            }
            if (route.authModal) {
                setAuthModalOpen(route.authModal);
            }
        };

        window.addEventListener('popstate', handlePopState);
        return () => window.removeEventListener('popstate', handlePopState);
    }, []);

    // Initial check for auth state or refresh user
    useEffect(() => {
        const token = localStorage.getItem('zacma_auth_token') || window.__INITIAL_TOKEN__;
        if (token) {
            try {
                localStorage.setItem('zacma_auth_token', token);
            } catch (_) {}

            api.getMe()
                .then(u => {
                    setUser(u);
                    api.getCrmLeads({ status: 'New' })
                        .then(res => setCrmNewCount(res.counts?.new || 0))
                        .catch(() => {});
                })
                .catch(() => {
                    if (!window.__INITIAL_USER__) {
                        localStorage.removeItem('zacma_auth_token');
                        setUser(null);
                    }
                })
                .finally(() => {
                    setAuthLoading(false);
                });
        } else {
            setAuthLoading(false);
        }
    }, []);

    const handleToggleFavorite = async (listingId: number) => {
        if (!user) {
            setAuthModalOpen('login');
            return;
        }

        try {
            await api.toggleFavorite(listingId);
            if (selectedListing && selectedListing.id === listingId) {
                setSelectedListing({
                    ...selectedListing,
                    is_favorited: !selectedListing.is_favorited,
                });
            }
        } catch (err) {
            console.error('Failed to toggle favorite', err);
        }
    };

    const handleLogout = async () => {
        await api.logout();
        setUser(null);
        navigateTo('browse');
    };

    const handleViewSellerProfile = (username: string) => {
        handleSelectListing(null);
        navigateTo('public-profile', { username });
    };

    const renderAuthLoading = () => (
        <div className="max-w-4xl mx-auto py-12 px-4 space-y-4 animate-pulse">
            <div className="h-8 bg-slate-200 rounded-2xl w-48"></div>
            <div className="h-64 bg-white rounded-3xl border border-slate-200/80 p-6 space-y-4">
                <div className="h-4 bg-slate-100 rounded w-3/4"></div>
                <div className="h-4 bg-slate-100 rounded w-1/2"></div>
                <div className="h-32 bg-slate-50 rounded-2xl"></div>
            </div>
        </div>
    );

    const renderAuthRequired = (title: string, description: string) => (
        <div className="text-center py-16 bg-white rounded-3xl border border-slate-200/80 p-8 max-w-md mx-auto shadow-xs my-8 animate-in fade-in duration-150">
            <div className="h-12 w-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 mx-auto mb-4">
                <Lock className="w-6 h-6" />
            </div>
            <h3 className="text-lg font-black text-slate-800">{title}</h3>
            <p className="text-xs text-slate-500 mt-1 mb-6 leading-relaxed">
                {description}
            </p>
            <div className="flex flex-col sm:flex-row items-center justify-center gap-2">
                <button
                    onClick={() => setAuthModalOpen('login')}
                    className="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black px-5 py-2.5 rounded-xl shadow-md transition"
                >
                    Log In to Your Account
                </button>
                <button
                    onClick={() => navigateTo('browse')}
                    className="w-full sm:w-auto bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl transition"
                >
                    Browse Marketplace
                </button>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col selection:bg-emerald-500 selection:text-white">
            <Navbar
                user={user}
                activeTab={activeTab}
                onTabChange={(tab) => navigateTo(tab)}
                onOpenPostListing={() => setPostListingOpen(true)}
                onOpenAuth={(mode) => setAuthModalOpen(mode)}
                onLogout={handleLogout}
                crmNewCount={crmNewCount}
            />

            <main className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-6">
                {activeTab === 'browse' && (
                    <MarketplaceBrowse
                        onSelectListing={handleSelectListing}
                        onToggleFavorite={handleToggleFavorite}
                    />
                )}

                {activeTab === 'my-listings' && (
                    authLoading ? renderAuthLoading() : (
                        user ? (
                            <MyListingsView
                                user={user}
                                onOpenPostListing={() => setPostListingOpen(true)}
                                onNavigatePricing={() => navigateTo('pricing')}
                                onSelectListing={handleSelectListing}
                            />
                        ) : renderAuthRequired(
                            'Sign In Required',
                            'You need to be signed in to manage your active listings and view analytics.'
                        )
                    )
                )}

                {activeTab === 'crm-leads' && (
                    authLoading ? renderAuthLoading() : (
                        user ? (
                            <CrmLeadsView />
                        ) : renderAuthRequired(
                            'Inbound Leads Require Sign In',
                            'You need to be signed in to view and manage your inbound customer CRM leads.'
                        )
                    )
                )}

                {activeTab === 'favorites' && (
                    authLoading ? renderAuthLoading() : (
                        user ? (
                            <FavoritesView
                                onSelectListing={handleSelectListing}
                                onBrowseMarket={() => navigateTo('browse')}
                            />
                        ) : renderAuthRequired(
                            'Saved Listings',
                            'Sign in to access your saved vehicle and property listings across all your devices.'
                        )
                    )
                )}

                {activeTab === 'pricing' && (
                    <SubscriptionView
                        user={user}
                        onRequireLogin={() => setAuthModalOpen('login')}
                    />
                )}

                {activeTab === 'requirements' && (
                    <BuyerRequirementsView
                        currentUser={user}
                        onRequireLogin={() => setAuthModalOpen('login')}
                        onDirectMessage={(sellerId) => {
                            setChatRecipientId(sellerId);
                            setChatListingId(null);
                            navigateTo('messages');
                        }}
                    />
                )}

                {activeTab === 'messages' && (
                    <MessagesView
                        currentUser={user}
                        initialRecipientId={chatRecipientId}
                        initialListingId={chatListingId}
                        onViewListing={(slug) => {
                            api.getListing(slug)
                                .then(l => handleSelectListing(l))
                                .catch(() => navigateTo('browse'));
                        }}
                    />
                )}

                {activeTab === 'public-profile' && viewingUsername && (
                    <PublicProfileView
                        username={viewingUsername}
                        onBack={() => {
                            navigateTo('browse');
                        }}
                        onSelectListing={handleSelectListing}
                        onToggleFavorite={handleToggleFavorite}
                    />
                )}

                {activeTab === 'profile' && (
                    authLoading ? renderAuthLoading() : (
                        user ? (
                            <ProfileSettingsView
                                user={user}
                                onUserUpdated={(updatedUser) => setUser(updatedUser)}
                                onNavigateTab={(tab, extra) => navigateTo(tab, extra)}
                            />
                        ) : renderAuthRequired(
                            'Profile Settings',
                            'You need to be logged in to view and edit your profile settings and dealership credentials.'
                        )
                    )
                )}

                {activeTab === 'admin' && (
                    authLoading ? renderAuthLoading() : (
                        user?.is_super_admin ? (
                            <AdminDashboardView />
                        ) : (
                            <div className="text-center py-16 bg-white rounded-3xl border border-rose-200 p-8 max-w-md mx-auto shadow-xs my-8 animate-in fade-in duration-150">
                                <div className="h-12 w-12 bg-rose-50 rounded-2xl flex items-center justify-center text-rose-600 mx-auto mb-4">
                                    <ShieldAlert className="w-6 h-6" />
                                </div>
                                <h3 className="text-lg font-black text-slate-800">Super Admin Required</h3>
                                <p className="text-xs text-slate-500 mt-1 mb-6 leading-relaxed">
                                    The Admin Command Center is reserved for authorized platform administrators.
                                </p>
                                <div className="flex flex-col sm:flex-row items-center justify-center gap-2">
                                    <button
                                        onClick={() => setAuthModalOpen('login')}
                                        className="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white text-xs font-black px-5 py-2.5 rounded-xl shadow-md transition"
                                    >
                                        Sign In as Admin
                                    </button>
                                    <button
                                        onClick={() => navigateTo('browse')}
                                        className="w-full sm:w-auto bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl transition"
                                    >
                                        Return to Marketplace
                                    </button>
                                </div>
                            </div>
                        )
                    )
                )}
            </main>

            {/* Modals */}
            <ListingDetailModal
                listing={selectedListing}
                currentUser={user}
                onClose={() => handleSelectListing(null)}
                onToggleFavorite={handleToggleFavorite}
                onViewProfile={handleViewSellerProfile}
                onStartChat={(sellerId, listingId) => {
                    handleSelectListing(null);
                    setChatRecipientId(sellerId);
                    setChatListingId(listingId);
                    navigateTo('messages');
                }}
            />

            {postListingOpen && (
                <AddListingModal
                    user={user}
                    onClose={() => setPostListingOpen(false)}
                    onListingCreated={() => {
                        navigateTo('my-listings');
                        api.getMe().then(u => setUser(u));
                    }}
                    onNavigatePricing={() => {
                        setPostListingOpen(false);
                        navigateTo('pricing');
                    }}
                    onRequireLogin={() => setAuthModalOpen('login')}
                />
            )}

            {authModalOpen && (
                <AuthModal
                    initialMode={authModalOpen}
                    onClose={() => setAuthModalOpen(null)}
                    onSuccess={(newUser) => {
                        setUser(newUser);
                    }}
                />
            )}

            {/* System-Wide Knowledge-Based AI Assistant */}
            <AiAssistantWidget
                user={user}
                activeTab={activeTab}
                onNavigateTab={(tab) => {
                    navigateTo(tab);
                }}
            />

            {/* Footer */}
            <footer className="bg-white border-t border-slate-200/80 py-8 mt-auto text-xs text-slate-500">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <div className="h-6 w-6 bg-emerald-600 rounded-lg flex items-center justify-center text-white font-bold text-xs">
                            Z
                        </div>
                        <span className="font-extrabold text-slate-800 text-sm">Zacma Marketplace</span>
                        <span>• Built for Ethiopia (ETB & Chapa)</span>
                    </div>
                    <div className="flex items-center gap-6">
                        <button onClick={() => navigateTo('browse')} className="hover:text-slate-800">Browse Listings</button>
                        <button onClick={() => navigateTo('pricing')} className="hover:text-slate-800">Pricing & Limits</button>
                        <span>© 2026 Zacma PLC</span>
                    </div>
                </div>
            </footer>
        </div>
    );
};

const container = document.getElementById('root');
if (container) {
    const root = createRoot(container);
    root.render(
        <ErrorBoundary>
            <App />
        </ErrorBoundary>
    );
}
