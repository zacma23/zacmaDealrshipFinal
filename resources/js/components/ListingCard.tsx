import React from 'react';
import { Listing } from '../types/marketplace';
import { Heart, MapPin, Calendar, Gauge, ShieldCheck, BedDouble, Building, Car, Home } from 'lucide-react';

interface ListingCardProps {
    listing: Listing;
    onSelect: (listing: Listing) => void;
    onToggleFavorite?: (listingId: number) => void;
}

export const ListingCard: React.FC<ListingCardProps> = ({
    listing,
    onSelect,
    onToggleFavorite,
}) => {
    const formatPrice = (price: number, currency = 'ETB') => {
        return `${currency} ${Number(price).toLocaleString('en-US')}`;
    };

    const getTypeIcon = () => {
        switch (listing.type) {
            case 'vehicle':
                return <Car className="w-3 h-3" />;
            case 'real_estate':
                return <Home className="w-3 h-3" />;
            case 'apartment':
                return <Building className="w-3 h-3" />;
            default:
                return null;
        }
    };

    const getTypeLabel = () => {
        switch (listing.type) {
            case 'vehicle':
                return 'Vehicle';
            case 'real_estate':
                return 'Real Estate';
            case 'apartment':
                return 'Apartment';
            default:
                return listing.type;
        }
    };

    return (
        <div className="bg-white rounded-2xl border border-slate-200/80 hover:border-emerald-500/40 shadow-xs hover:shadow-lg transition duration-200 overflow-hidden flex flex-col group cursor-pointer"
             onClick={() => onSelect(listing)}
        >
            {/* Image Container */}
            <div className="relative aspect-16/10 w-full bg-slate-100 overflow-hidden">
                <img
                    src={listing.primary_image}
                    alt={listing.title}
                    className="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                    loading="lazy"
                />

                {/* Badges */}
                <div className="absolute top-3 left-3 flex flex-wrap gap-1.5 z-10">
                    <span className="inline-flex items-center gap-1 bg-slate-900/80 backdrop-blur-sm text-white text-[11px] font-bold px-2 py-0.5 rounded-md">
                        {getTypeIcon()}
                        <span>{getTypeLabel()}</span>
                    </span>
                    {listing.featured && (
                        <span className="bg-amber-500 text-slate-950 text-[10px] font-black uppercase px-2 py-0.5 rounded-md shadow-xs">
                            Featured
                        </span>
                    )}
                </div>

                {/* Favorite button */}
                <button
                    type="button"
                    onClick={(e) => {
                        e.stopPropagation();
                        onToggleFavorite?.(listing.id);
                    }}
                    className={`absolute top-3 right-3 p-2 rounded-full backdrop-blur-md transition shadow-sm z-10 ${
                        listing.is_favorited
                            ? 'bg-rose-500 text-white hover:bg-rose-600'
                            : 'bg-white/80 text-slate-700 hover:bg-white hover:text-rose-500'
                    }`}
                >
                    <Heart className={`w-4 h-4 ${listing.is_favorited ? 'fill-current' : ''}`} />
                </button>

                {/* Price tag */}
                <div className="absolute bottom-3 left-3 bg-slate-950/85 backdrop-blur-md px-2.5 py-1 rounded-lg">
                    <span className="text-white font-extrabold text-sm sm:text-base tracking-tight">
                        {formatPrice(listing.price, listing.currency)}
                    </span>
                </div>
            </div>

            {/* Content */}
            <div className="p-4 flex-1 flex flex-col justify-between">
                <div>
                    <div className="flex items-center gap-1 text-[11px] text-slate-500 mb-1">
                        <MapPin className="w-3 h-3 text-emerald-600 shrink-0" />
                        <span className="truncate font-medium">{listing.city}{listing.address ? ` • ${listing.address}` : ''}</span>
                    </div>

                    <h3 className="font-bold text-slate-900 text-sm sm:text-base line-clamp-1 group-hover:text-emerald-700 transition">
                        {listing.title}
                    </h3>

                    {/* Dynamic Attributes Pills */}
                    <div className="mt-2.5 flex flex-wrap gap-1.5 text-[11px] font-medium text-slate-600">
                        {listing.type === 'vehicle' && (
                            <>
                                {listing.year && (
                                    <span className="bg-slate-100 px-2 py-0.5 rounded-md flex items-center gap-1">
                                        <Calendar className="w-3 h-3 text-slate-400" />
                                        {listing.year}
                                    </span>
                                )}
                                {listing.listing_attributes?.transmission && (
                                    <span className="bg-slate-100 px-2 py-0.5 rounded-md">
                                        {listing.listing_attributes.transmission}
                                    </span>
                                )}
                                {listing.listing_attributes?.fuel_type && (
                                    <span className="bg-slate-100 px-2 py-0.5 rounded-md">
                                        {listing.listing_attributes.fuel_type}
                                    </span>
                                )}
                            </>
                        )}

                        {(listing.type === 'real_estate' || listing.type === 'apartment') && (
                            <>
                                {listing.bedrooms && (
                                    <span className="bg-slate-100 px-2 py-0.5 rounded-md flex items-center gap-1">
                                        <BedDouble className="w-3 h-3 text-slate-400" />
                                        {listing.bedrooms} Beds
                                    </span>
                                )}
                                {listing.listing_attributes?.property_purpose && (
                                    <span className="bg-emerald-50 text-emerald-700 font-bold px-2 py-0.5 rounded-md uppercase text-[10px]">
                                        For {listing.listing_attributes.property_purpose}
                                    </span>
                                )}
                                {listing.listing_attributes?.furnished === 'yes' && (
                                    <span className="bg-blue-50 text-blue-700 px-2 py-0.5 rounded-md text-[10px] font-semibold">
                                        Furnished
                                    </span>
                                )}
                            </>
                        )}
                    </div>
                </div>

                {/* Seller snippet footer */}
                <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                    <div className="flex items-center gap-2 truncate">
                        <img
                            src={listing.seller?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(listing.seller?.name || 'User')}&background=10B981&color=FFFFFF`}
                            alt={listing.seller?.name}
                            className="w-5 h-5 rounded-full object-cover"
                        />
                        <span className="text-slate-600 font-medium truncate">{listing.seller?.name}</span>
                        {listing.seller?.is_verified && (
                            <span title="Verified Seller" className="inline-flex shrink-0">
                                <ShieldCheck className="w-3.5 h-3.5 text-emerald-600" />
                            </span>
                        )}
                    </div>
                    <span className="text-[10px] text-slate-400 shrink-0">View Details →</span>
                </div>
            </div>
        </div>
    );
};

