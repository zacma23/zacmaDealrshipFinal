import React, { useState, useEffect } from 'react';
import { Listing } from '../types/marketplace';
import { api } from '../services/api';
import { ListingCard } from './ListingCard';
import { 
    ShieldCheck, MapPin, Phone, Calendar, 
    ArrowLeft, UserCheck, Layers, ExternalLink
} from 'lucide-react';

interface PublicProfileViewProps {
    username: string;
    onBack: () => void;
    onSelectListing: (listing: Listing) => void;
    onToggleFavorite: (listingId: number) => void;
}

export const PublicProfileView: React.FC<PublicProfileViewProps> = ({
    username,
    onBack,
    onSelectListing,
    onToggleFavorite,
}) => {
    const [profileData, setProfileData] = useState<any>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        setLoading(true);
        api.getPublicProfile(username)
            .then(data => setProfileData(data))
            .catch(err => {
                console.error('Failed to load profile', err);
                setError('Seller profile not found.');
            })
            .finally(() => setLoading(false));
    }, [username]);

    if (loading) {
        return (
            <div className="max-w-4xl mx-auto p-6 space-y-4 animate-pulse">
                <div className="h-32 bg-slate-200 rounded-3xl"></div>
                <div className="h-48 bg-slate-200 rounded-3xl"></div>
            </div>
        );
    }

    if (error || !profileData) {
        return (
            <div className="max-w-md mx-auto p-12 text-center bg-white rounded-3xl border border-slate-200 space-y-3">
                <h3 className="font-bold text-slate-900 text-base">{error || 'Profile not found'}</h3>
                <button
                    onClick={onBack}
                    className="bg-emerald-600 text-white text-xs font-bold px-4 py-2 rounded-xl"
                >
                    Back to Marketplace
                </button>
            </div>
        );
    }

    const { user, listings } = profileData;

    return (
        <div className="max-w-6xl mx-auto space-y-8 pb-16">
            {/* Top Navigation */}
            <button
                onClick={onBack}
                className="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 transition"
            >
                <ArrowLeft className="w-4 h-4" />
                <span>Back to Marketplace</span>
            </button>

            {/* Profile Card */}
            <div className="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-start sm:items-center gap-6">
                <img
                    src={user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=10B981&color=FFFFFF`}
                    alt={user.name}
                    className="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl object-cover ring-4 ring-emerald-500/20 shadow-md shrink-0"
                />

                <div className="space-y-2 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <h1 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            {user.name}
                        </h1>

                        {/* Verification badge placeholder */}
                        <span className="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-0.5 rounded-full border border-emerald-200">
                            <ShieldCheck className="w-3.5 h-3.5 text-emerald-600" />
                            <span>Verified Seller</span>
                        </span>
                    </div>

                    <p className="text-xs text-slate-500">@{user.username || 'seller'}</p>

                    {user.bio && (
                        <p className="text-xs text-slate-700 leading-relaxed max-w-2xl">
                            {user.bio}
                        </p>
                    )}

                    <div className="flex flex-wrap items-center gap-4 text-xs text-slate-500 pt-2 font-medium">
                        <span className="flex items-center gap-1">
                            <MapPin className="w-3.5 h-3.5 text-emerald-600" />
                            <span>{user.city || 'Addis Ababa, Ethiopia'}</span>
                        </span>

                        {user.phone && (
                            <span className="flex items-center gap-1">
                                <Phone className="w-3.5 h-3.5 text-slate-400" />
                                <span>{user.phone}</span>
                            </span>
                        )}

                        <span className="flex items-center gap-1">
                            <Calendar className="w-3.5 h-3.5 text-slate-400" />
                            <span>Member since {user.member_since || '2026'}</span>
                        </span>
                    </div>
                </div>
            </div>

            {/* Active Listings by this Seller */}
            <div className="space-y-4">
                <div className="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h2 className="text-lg font-black text-slate-900 flex items-center gap-2">
                        <Layers className="w-5 h-5 text-emerald-600" />
                        <span>Active Marketplace Listings ({listings?.total || listings?.data?.length || 0})</span>
                    </h2>
                </div>

                {listings?.data && listings.data.length > 0 ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                        {listings.data.map((listing: Listing) => (
                            <ListingCard
                                key={listing.id}
                                listing={listing}
                                onSelect={onSelectListing}
                                onToggleFavorite={onToggleFavorite}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="bg-white rounded-3xl p-12 text-center border border-slate-200 text-xs text-slate-500">
                        This seller does not currently have any active published listings.
                    </div>
                )}
            </div>
        </div>
    );
};

