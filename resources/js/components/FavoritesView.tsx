import React, { useState, useEffect } from 'react';
import { Listing } from '../types/marketplace';
import { api } from '../services/api';
import { ListingCard } from './ListingCard';
import { Heart, Search } from 'lucide-react';

interface FavoritesViewProps {
    onSelectListing: (listing: Listing) => void;
    onBrowseMarket: () => void;
}

export const FavoritesView: React.FC<FavoritesViewProps> = ({
    onSelectListing,
    onBrowseMarket,
}) => {
    const [favorites, setFavorites] = useState<Listing[]>([]);
    const [loading, setLoading] = useState(true);

    const fetchFavorites = async () => {
        setLoading(true);
        try {
            const data = await api.getFavorites();
            setFavorites(data.data || []);
        } catch (err) {
            console.error('Failed to load favorites', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchFavorites();
    }, []);

    const handleToggleFavorite = async (listingId: number) => {
        try {
            await api.toggleFavorite(listingId);
            fetchFavorites();
        } catch (err) {
            console.error('Failed to toggle favorite', err);
        }
    };

    return (
        <div className="space-y-6 pb-16">
            <div>
                <h1 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <Heart className="w-6 h-6 text-rose-500 fill-current" />
                    <span>Saved Favorites</span>
                </h1>
                <p className="text-xs text-slate-500">Listings you've bookmarked to keep track of prices or contact later.</p>
            </div>

            {loading ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    {[1, 2, 3, 4].map(n => (
                        <div key={n} className="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-3 animate-pulse">
                            <div className="aspect-16/10 bg-slate-200 rounded-xl"></div>
                            <div className="h-4 bg-slate-200 rounded-md w-3/4"></div>
                        </div>
                    ))}
                </div>
            ) : favorites.length > 0 ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    {favorites.map(listing => (
                        <ListingCard
                            key={listing.id}
                            listing={{ ...listing, is_favorited: true }}
                            onSelect={onSelectListing}
                            onToggleFavorite={handleToggleFavorite}
                        />
                    ))}
                </div>
            ) : (
                <div className="bg-white rounded-3xl border border-slate-200 p-12 text-center max-w-md mx-auto space-y-3">
                    <Heart className="w-10 h-10 text-slate-300 mx-auto" />
                    <h3 className="font-bold text-slate-900 text-base">No Saved Listings</h3>
                    <p className="text-xs text-slate-500">
                        Click the heart icon on any listing while browsing the marketplace to save it here.
                    </p>
                    <button
                        onClick={onBrowseMarket}
                        className="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition"
                    >
                        Browse Listings
                    </button>
                </div>
            )}
        </div>
    );
};

