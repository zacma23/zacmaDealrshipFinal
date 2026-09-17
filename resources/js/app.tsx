import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { Listing, User } from './types/marketplace';
import { api } from './services/api';
import { Navbar } from './components/Navbar';
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
        case 'profile': return '/profile';
        case 'public-profile': return username ? `/profile/${username}` : '/browse';
        default: return '/';
    }
};

const resolveRoute = (): { tab: string; username: string | null; authModal: 'login' | 'register' | null } => {
    const path = window.location.pathname.toLowerCase();
    const search = new URLSearchParams(window.location.search);

    if (search.get('payment') === 'success') {
        return { tab: 'pricing', username: null, authModal: null };
    }
    if (search.get('auth') === 'login' || path === '/login') {
        return { tab: 'browse', username: null, authModal: 'login' };
    }
    if (search.get('auth') === 'register' || path === '/register') {
        return { tab: 'browse', username: null, authModal: 'register' };
    }

    if (path.startsWith('/profile/')) {
        const u = window.location.pathname.replace(/^\/profile\//i, '').split('/')[0];
        if (u) {
            return { tab: 'public-profile', username: decodeURIComponent(u), authModal: null };
        }
    }
    if (path === '/profile' || path === '/settings' || path === '/account') {
        return { tab: 'profile', username: null, authModal: null };
    }
    if (path === '/my-listings') {
        return { tab: 'my-listings', username: null, authModal: null };
    }
    if (path === '/crm-leads' || path === '/leads') {
        return { tab: 'crm-leads', username: null, authModal: null };
    }
    if (path === '/favorites' || path === '/saved') {
        return { tab: 'favorites', username: null, authModal: null };
    }
    if (path === '/messages' || path.startsWith('/messages/')) {
        return { tab: 'messages', username: null, authModal: null };
    }
    if (path === '/requirements' || path === '/buyer-requirements') {
        return { tab: 'requirements', username: null, authModal: null };
    }
    if (path === '/pricing' || path === '/plans' || path === '/subscriptions') {
        return { tab: 'pricing', username: null, authModal: null };
    }
    if (path === '/admin') {
        return { tab: 'admin', username: null, authModal: null };
    }

    return { tab: 'browse', username: null, authModal: null };
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

    const navigateTo = (tab: string, extra: { username?: string | null; push?: boolean } = {}) => {
        const { username = null, push = true } = extra;
        setActiveTab(tab);
        if (tab === 'public-profile') {
            setViewingUsername(username);
        } else {
            setViewingUsername(null);
        }

        if (push) {
            const targetPath = getPathForTab(tab, username);
            if (window.location.pathname !== targetPath) {
                window.history.pushState({ tab, username }, '', targetPath);
            }
        }
    };

    // Listen to browser Back and Forward navigation (popstate)
    useEffect(() => {
        const handlePopState = (e: PopStateEvent) => {
            if (e.state && e.state.tab) {
                setActiveTab(e.state.tab);
                setViewingUsername(e.state.username || null);
            } else {
                const route = resolveRoute();
                setActiveTab(route.tab);
                setViewingUsername(route.username);
                if (route.authModal) {
                    setAuthModalOpen(route.authModal);
                }
            }
        };

        window.addEventListener('popstate', handlePopState);
        return () => window.removeEventListener('popstate', handlePopState);
    }, []);

    // Initial check for auth state or refresh user
    useEffect(() => {
        const token = localStorage.getItem('zacma_auth_token');
        if (token || window.__INITIAL_USER__) {
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
                });
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
        setSelectedListing(null);
        navigateTo('public-profile', { username });
    };

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
                        onSelectListing={(listing) => setSelectedListing(listing)}
                        onToggleFavorite={handleToggleFavorite}
                    />
                )}

                {activeTab === 'my-listings' && user && (
                    <MyListingsView
                        user={user}
                        onOpenPostListing={() => setPostListingOpen(true)}
                        onNavigatePricing={() => navigateTo('pricing')}
                        onSelectListing={(listing) => setSelectedListing(listing)}
                    />
                )}

                {activeTab === 'crm-leads' && user && (
                    <CrmLeadsView />
                )}

                {activeTab === 'favorites' && user && (
                    <FavoritesView
                        onSelectListing={(listing) => setSelectedListing(listing)}
                        onBrowseMarket={() => navigateTo('browse')}
                    />
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
                            navigateTo('browse');
                        }}
                    />
                )}

                {activeTab === 'public-profile' && viewingUsername && (
                    <PublicProfileView
                        username={viewingUsername}
                        onBack={() => {
                            navigateTo('browse');
                        }}
                        onSelectListing={(listing) => setSelectedListing(listing)}
                        onToggleFavorite={handleToggleFavorite}
                    />
                )}

                {activeTab === 'profile' && (
                    user ? (
                        <ProfileSettingsView
                            user={user}
                            onUserUpdated={(updatedUser) => setUser(updatedUser)}
                            onNavigateTab={(tab, extra) => navigateTo(tab, extra)}
                        />
                    ) : (
                        <div className="text-center py-16 bg-white rounded-3xl border border-slate-200/80 p-8 max-w-md mx-auto shadow-xs">
                            <h3 className="text-lg font-black text-slate-800">Sign In Required</h3>
                            <p className="text-xs text-slate-500 mt-1 mb-5">
                                You need to be logged in to view and edit your profile settings and dealership credentials.
                            </p>
                            <button
                                onClick={() => setAuthModalOpen('login')}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black px-5 py-2.5 rounded-xl shadow-md transition"
                            >
                                Log In to Your Account
                            </button>
                        </div>
                    )
                )}

                {activeTab === 'admin' && user?.is_super_admin && (
                    <AdminDashboardView />
                )}
            </main>

            {/* Modals */}
            <ListingDetailModal
                listing={selectedListing}
                currentUser={user}
                onClose={() => setSelectedListing(null)}
                onToggleFavorite={handleToggleFavorite}
                onViewProfile={handleViewSellerProfile}
                onStartChat={(sellerId, listingId) => {
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
    root.render(<App />);
}
