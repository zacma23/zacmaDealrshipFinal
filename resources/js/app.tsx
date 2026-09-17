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
import { AdminDashboardView } from './components/AdminDashboardView';
import { AuthModal } from './components/AuthModal';

declare global {
    interface Window {
        __INITIAL_USER__?: User | null;
        __APP_NAME__?: string;
    }
}

export const App: React.FC = () => {
    const [user, setUser] = useState<User | null>(window.__INITIAL_USER__ || null);
    const [activeTab, setActiveTab] = useState<string>('browse');
    const [selectedListing, setSelectedListing] = useState<Listing | null>(null);
    const [viewingUsername, setViewingUsername] = useState<string | null>(null);

    const [postListingOpen, setPostListingOpen] = useState(false);
    const [authModalOpen, setAuthModalOpen] = useState<'login' | 'register' | null>(null);
    const [crmNewCount, setCrmNewCount] = useState(0);

    // Initial check for route or user
    useEffect(() => {
        // If profile path in URL
        const path = window.location.pathname;
        if (path.startsWith('/profile/')) {
            const u = path.replace('/profile/', '').split('/')[0];
            if (u) {
                setViewingUsername(u);
                setActiveTab('public-profile');
            }
        }

        // Try to fetch current user if token exists in localStorage but window user is null
        const token = localStorage.getItem('zacma_auth_token');
        if (token || window.__INITIAL_USER__) {
            api.getMe()
                .then(u => {
                    setUser(u);
                    // Fetch CRM leads count for notification badge
                    api.getCrmLeads({ status: 'New' })
                        .then(res => setCrmNewCount(res.counts?.new || 0))
                        .catch(() => {});
                })
                .catch(() => {
                    // Token expired or invalid
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
        setActiveTab('browse');
    };

    const handleViewSellerProfile = (username: string) => {
        setSelectedListing(null);
        setViewingUsername(username);
        setActiveTab('public-profile');
    };

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col selection:bg-emerald-500 selection:text-white">
            <Navbar
                user={user}
                activeTab={activeTab}
                onTabChange={(tab) => {
                    setActiveTab(tab);
                    if (tab !== 'public-profile') setViewingUsername(null);
                }}
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
                        onNavigatePricing={() => setActiveTab('pricing')}
                        onSelectListing={(listing) => setSelectedListing(listing)}
                    />
                )}

                {activeTab === 'crm-leads' && user && (
                    <CrmLeadsView />
                )}

                {activeTab === 'favorites' && user && (
                    <FavoritesView
                        onSelectListing={(listing) => setSelectedListing(listing)}
                        onBrowseMarket={() => setActiveTab('browse')}
                    />
                )}

                {activeTab === 'pricing' && (
                    <SubscriptionView
                        user={user}
                        onRequireLogin={() => setAuthModalOpen('login')}
                    />
                )}

                {activeTab === 'public-profile' && viewingUsername && (
                    <PublicProfileView
                        username={viewingUsername}
                        onBack={() => {
                            setViewingUsername(null);
                            setActiveTab('browse');
                        }}
                        onSelectListing={(listing) => setSelectedListing(listing)}
                        onToggleFavorite={handleToggleFavorite}
                    />
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
            />

            {postListingOpen && (
                <AddListingModal
                    user={user}
                    onClose={() => setPostListingOpen(false)}
                    onListingCreated={() => {
                        setActiveTab('my-listings');
                        // Refresh user quota
                        api.getMe().then(u => setUser(u));
                    }}
                    onNavigatePricing={() => {
                        setPostListingOpen(false);
                        setActiveTab('pricing');
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
                        <button onClick={() => setActiveTab('browse')} className="hover:text-slate-800">Browse Listings</button>
                        <button onClick={() => setActiveTab('pricing')} className="hover:text-slate-800">Pricing & Limits</button>
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

