import React, { useState } from 'react';
import { Listing, User } from '../types/marketplace';
import { api } from '../services/api';
import { 
    X, Heart, MapPin, Calendar, BedDouble, Bath, 
    Share2, ShieldCheck, Mail, Phone, Send, CheckCircle2,
    Car, Home, Building2, User as UserIcon, AlertCircle
} from 'lucide-react';

interface ListingDetailModalProps {
    listing: Listing | null;
    currentUser: User | null;
    onClose: () => void;
    onToggleFavorite: (listingId: number) => void;
    onViewProfile: (username: string) => void;
}

export const ListingDetailModal: React.FC<ListingDetailModalProps> = ({
    listing,
    currentUser,
    onClose,
    onToggleFavorite,
    onViewProfile,
}) => {
    if (!listing) return null;

    const [selectedImage, setSelectedImage] = useState<string>(
        listing.primary_image || (listing.images?.[0]?.url ?? '')
    );
    const [inquiryName, setInquiryName] = useState(currentUser?.name || '');
    const [inquiryEmail, setInquiryEmail] = useState(currentUser?.email || '');
    const [inquiryPhone, setInquiryPhone] = useState(currentUser?.phone || '');
    const [inquiryMessage, setInquiryMessage] = useState(
        `Hello ${listing.seller?.name || 'Seller'}, I am interested in your listing "${listing.title}". Is it still available?`
    );
    const [submittingInquiry, setSubmittingInquiry] = useState(false);
    const [inquirySuccess, setInquirySuccess] = useState(false);
    const [inquiryError, setInquiryError] = useState<string | null>(null);

    const isOwnListing = currentUser?.id === listing.user_id;

    const handleSendInquiry = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmittingInquiry(true);
        setInquiryError(null);

        try {
            await api.sendInquiry({
                listing_id: listing.id,
                name: inquiryName,
                email: inquiryEmail,
                phone: inquiryPhone,
                message: inquiryMessage,
            });
            setInquirySuccess(true);
        } catch (err: any) {
            setInquiryError(err.response?.data?.message || 'Failed to submit inquiry. Please try again.');
        } finally {
            setSubmittingInquiry(false);
        }
    };

    const formatPrice = (price: number, currency = 'ETB') => {
        return `${currency} ${Number(price).toLocaleString('en-US')}`;
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 animate-in fade-in duration-150">
            <div className="bg-white rounded-3xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200">
                {/* Modal Header */}
                <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <div className="flex items-center gap-2">
                        <span className="text-xs font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full">
                            {listing.type.replace('_', ' ')}
                        </span>
                        <span className="text-xs text-slate-500 font-medium">
                            Category: {listing.category_name || 'General'}
                        </span>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => onToggleFavorite(listing.id)}
                            className={`p-2 rounded-full border transition ${
                                listing.is_favorited
                                    ? 'bg-rose-50 border-rose-200 text-rose-600'
                                    : 'border-slate-200 text-slate-600 hover:bg-slate-100'
                            }`}
                            title="Favorite"
                        >
                            <Heart className={`w-4 h-4 ${listing.is_favorited ? 'fill-current' : ''}`} />
                        </button>
                        <button
                            onClick={onClose}
                            className="p-2 rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                        >
                            <X className="w-5 h-5" />
                        </button>
                    </div>
                </div>

                {/* Modal Body */}
                <div className="flex-1 overflow-y-auto p-6 space-y-6">
                    {/* Media Gallery */}
                    <div className="space-y-3">
                        <div className="aspect-16/9 sm:aspect-21/9 w-full bg-slate-900 rounded-2xl overflow-hidden relative shadow-inner">
                            <img
                                src={selectedImage || listing.primary_image}
                                alt={listing.title}
                                className="w-full h-full object-contain"
                            />
                            <div className="absolute bottom-3 left-3 bg-slate-950/80 backdrop-blur-md px-3 py-1.5 rounded-xl border border-white/10">
                                <span className="text-white font-black text-lg sm:text-xl">
                                    {formatPrice(listing.price, listing.currency)}
                                </span>
                            </div>
                        </div>

                        {/* Thumbnails */}
                        {listing.images && listing.images.length > 1 && (
                            <div className="flex gap-2 overflow-x-auto pb-1">
                                {listing.images.map((img) => (
                                    <button
                                        key={img.id}
                                        onClick={() => setSelectedImage(img.url)}
                                        className={`w-16 h-12 rounded-lg overflow-hidden border-2 transition shrink-0 ${
                                            selectedImage === img.url ? 'border-emerald-500 scale-105' : 'border-transparent opacity-70 hover:opacity-100'
                                        }`}
                                    >
                                        <img src={img.url} alt="" className="w-full h-full object-cover" />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Title & Location */}
                    <div>
                        <div className="flex items-center gap-1.5 text-xs text-slate-500 font-medium mb-1">
                            <MapPin className="w-3.5 h-3.5 text-emerald-600" />
                            <span>{listing.city}</span>
                            {listing.address && <span>• {listing.address}</span>}
                        </div>
                        <h1 className="text-xl sm:text-2xl font-black text-slate-900 leading-snug">
                            {listing.title}
                        </h1>
                    </div>

                    {/* Attributes Grid */}
                    <div className="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <h2 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Specifications & Details</h2>
                        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                            <div className="bg-white p-3 rounded-xl border border-slate-200/60">
                                <span className="text-slate-400 block text-[11px]">Type</span>
                                <span className="font-bold text-slate-800 capitalize">{listing.type.replace('_', ' ')}</span>
                            </div>

                            {listing.year && (
                                <div className="bg-white p-3 rounded-xl border border-slate-200/60">
                                    <span className="text-slate-400 block text-[11px]">Year</span>
                                    <span className="font-bold text-slate-800">{listing.year}</span>
                                </div>
                            )}

                            {listing.bedrooms && (
                                <div className="bg-white p-3 rounded-xl border border-slate-200/60">
                                    <span className="text-slate-400 block text-[11px]">Bedrooms</span>
                                    <span className="font-bold text-slate-800">{listing.bedrooms} Beds</span>
                                </div>
                            )}

                            {/* Dynamically render listing_attributes */}
                            {listing.listing_attributes && Object.entries(listing.listing_attributes).map(([key, value]) => {
                                if (value === null || value === undefined || value === '') return null;
                                const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                const formattedValue = typeof value === 'boolean' 
                                    ? (value ? 'Yes' : 'No') 
                                    : String(value);

                                return (
                                    <div key={key} className="bg-white p-3 rounded-xl border border-slate-200/60">
                                        <span className="text-slate-400 block text-[11px]">{formattedKey}</span>
                                        <span className="font-bold text-slate-800 capitalize">{formattedValue}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Description */}
                    {listing.description && (
                        <div>
                            <h2 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Description</h2>
                            <p className="text-sm text-slate-700 whitespace-pre-line leading-relaxed bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                                {listing.description}
                            </p>
                        </div>
                    )}

                    {/* Two-Column Section: Seller Profile & Contact Form */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        {/* Seller Card */}
                        <div className="bg-slate-50 rounded-2xl p-5 border border-slate-200/80 flex flex-col justify-between">
                            <div>
                                <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Listed by Seller</h3>
                                <div className="flex items-center gap-3 mb-3">
                                    <img
                                        src={listing.seller?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(listing.seller?.name || 'User')}&background=10B981&color=FFFFFF`}
                                        alt={listing.seller?.name}
                                        className="w-12 h-12 rounded-2xl object-cover ring-2 ring-emerald-500/30"
                                    />
                                    <div>
                                        <div className="flex items-center gap-1.5">
                                            <h4 className="font-bold text-slate-900 text-sm">{listing.seller?.name}</h4>
                                            {listing.seller?.is_verified && (
                                                <ShieldCheck className="w-4 h-4 text-emerald-600" title="Verified Seller" />
                                            )}
                                        </div>
                                        <p className="text-xs text-slate-500">{listing.seller?.city || 'Addis Ababa'}</p>
                                    </div>
                                </div>
                                <p className="text-xs text-slate-600 mb-4 leading-relaxed">
                                    One account on Zacma allows buyers and sellers to connect seamlessly with zero intermediaries.
                                </p>
                            </div>

                            {listing.seller?.username && (
                                <button
                                    onClick={() => onViewProfile(listing.seller!.username!)}
                                    className="w-full text-center bg-white hover:bg-slate-100 text-slate-800 text-xs font-bold py-2.5 rounded-xl border border-slate-200 transition"
                                >
                                    View Full Seller Profile & Active Listings →
                                </button>
                            )}
                        </div>

                        {/* Contact Seller Inquiry Form */}
                        <div className="bg-emerald-50/50 rounded-2xl p-5 border border-emerald-100">
                            <div className="flex items-center justify-between mb-3">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-emerald-800 flex items-center gap-1.5">
                                    <Mail className="w-3.5 h-3.5" />
                                    <span>Contact Seller (Creates CRM Lead)</span>
                                </h3>
                            </div>

                            {isOwnListing ? (
                                <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-800 flex items-center gap-2">
                                    <AlertCircle className="w-4 h-4 shrink-0 text-amber-600" />
                                    <span>You own this listing. Inquiries from interested buyers will appear directly in your CRM Leads inbox.</span>
                                </div>
                            ) : inquirySuccess ? (
                                <div className="bg-emerald-100 border border-emerald-300 rounded-xl p-4 text-emerald-900 text-xs text-center space-y-2">
                                    <CheckCircle2 className="w-6 h-6 text-emerald-600 mx-auto" />
                                    <p className="font-bold">Inquiry Sent Successfully!</p>
                                    <p className="text-emerald-700 text-[11px]">
                                        Your contact info and message have been routed to the seller's CRM pipeline. They will reach out to you shortly.
                                    </p>
                                </div>
                            ) : (
                                <form onSubmit={handleSendInquiry} className="space-y-2.5 text-xs">
                                    {inquiryError && (
                                        <div className="bg-rose-50 text-rose-700 p-2 rounded-lg border border-rose-200 text-[11px]">
                                            {inquiryError}
                                        </div>
                                    )}

                                    <div>
                                        <label className="block text-slate-600 font-semibold mb-1">Your Full Name</label>
                                        <input
                                            type="text"
                                            required
                                            value={inquiryName}
                                            onChange={(e) => setInquiryName(e.target.value)}
                                            placeholder="e.g. Abebe Kebede"
                                            className="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                        />
                                    </div>

                                    <div className="grid grid-cols-2 gap-2">
                                        <div>
                                            <label className="block text-slate-600 font-semibold mb-1">Email</label>
                                            <input
                                                type="email"
                                                required
                                                value={inquiryEmail}
                                                onChange={(e) => setInquiryEmail(e.target.value)}
                                                placeholder="you@email.com"
                                                className="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-slate-600 font-semibold mb-1">Phone (Optional)</label>
                                            <input
                                                type="tel"
                                                value={inquiryPhone}
                                                onChange={(e) => setInquiryPhone(e.target.value)}
                                                placeholder="+251 91..."
                                                className="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-slate-600 font-semibold mb-1">Your Message</label>
                                        <textarea
                                            required
                                            rows={3}
                                            value={inquiryMessage}
                                            onChange={(e) => setInquiryMessage(e.target.value)}
                                            className="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none resize-none"
                                        ></textarea>
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={submittingInquiry}
                                        className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm shadow-emerald-600/20 disabled:opacity-50"
                                    >
                                        <Send className="w-3.5 h-3.5" />
                                        <span>{submittingInquiry ? 'Sending...' : 'Send Message to Seller'}</span>
                                    </button>
                                </form>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

