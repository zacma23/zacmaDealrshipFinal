import React, { useState, useEffect } from 'react';
import { Listing, Review, User } from '../types/marketplace';
import { api } from '../services/api';
import { 
    X, Heart, MapPin, Calendar, BedDouble, Bath, 
    Share2, ShieldCheck, Mail, Phone, Send, CheckCircle2,
    Car, Home, Building2, User as UserIcon, AlertCircle,
    Star, MessageSquare
} from 'lucide-react';

interface ListingDetailModalProps {
    listing: Listing | null;
    currentUser: User | null;
    onClose: () => void;
    onToggleFavorite: (listingId: number) => void;
    onViewProfile: (username: string) => void;
    onStartChat?: (sellerId: number, listingId: number) => void;
}

export const ListingDetailModal: React.FC<ListingDetailModalProps> = ({
    listing,
    currentUser,
    onClose,
    onToggleFavorite,
    onViewProfile,
    onStartChat,
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

    // Reviews state
    const [reviews, setReviews] = useState<Review[]>([]);
    const [avgRating, setAvgRating] = useState(5.0);
    const [reviewRating, setReviewRating] = useState(5);
    const [reviewComment, setReviewComment] = useState('');
    const [submittingReview, setSubmittingReview] = useState(false);
    const [reviewSuccess, setReviewSuccess] = useState(false);

    const isOwnListing = currentUser?.id === listing.user_id;

    useEffect(() => {
        api.getListingReviews(listing.id)
            .then(res => {
                setReviews(res.data || []);
                setAvgRating(res.average_rating || 5.0);
            })
            .catch(err => console.error('Failed to load reviews', err));
    }, [listing.id]);

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

    const handleReviewSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!currentUser) return;
        setSubmittingReview(true);
        try {
            const res = await api.submitReview(listing.id, {
                rating: reviewRating,
                comment: reviewComment,
            });
            setReviews(prev => [res.data, ...prev]);
            setReviewSuccess(true);
            setReviewComment('');
        } catch (err) {
            console.error('Failed to submit review', err);
        } finally {
            setSubmittingReview(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
            <div className="relative bg-white rounded-3xl max-w-4xl w-full overflow-hidden shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
                {/* Close Button */}
                <button
                    onClick={onClose}
                    className="absolute top-4 right-4 z-20 bg-white/80 hover:bg-white text-slate-700 p-2 rounded-full shadow-md backdrop-blur transition"
                    aria-label="Close modal"
                >
                    <X className="w-5 h-5" />
                </button>

                {/* Modal Content */}
                <div className="max-h-[90vh] overflow-y-auto">
                    {/* Gallery & Header */}
                    <div className="p-6 space-y-6">
                        {/* Main Image */}
                        <div className="relative h-80 sm:h-96 w-full rounded-2xl overflow-hidden bg-slate-100">
                            <img
                                src={selectedImage}
                                alt={listing.title}
                                className="w-full h-full object-cover"
                            />
                            <div className="absolute top-4 left-4 flex gap-2">
                                <span className="bg-slate-900/80 backdrop-blur text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                                    {listing.type.replace('_', ' ')}
                                </span>
                                {listing.featured && (
                                    <span className="bg-emerald-500 text-slate-950 text-xs font-black px-3 py-1 rounded-full uppercase tracking-wider">
                                        Featured
                                    </span>
                                )}
                            </div>
                            <button
                                onClick={() => onToggleFavorite(listing.id)}
                                className="absolute top-4 right-14 bg-white/90 hover:bg-white p-2.5 rounded-full shadow-md backdrop-blur transition group"
                                aria-label="Save listing"
                            >
                                <Heart
                                    className={`w-5 h-5 transition ${
                                        listing.is_favorited
                                            ? 'fill-rose-500 text-rose-500'
                                            : 'text-slate-600 group-hover:text-rose-500'
                                    }`}
                                />
                            </button>
                        </div>

                        {/* Thumbnail Row */}
                        {listing.images && listing.images.length > 1 && (
                            <div className="flex gap-2 overflow-x-auto pb-2">
                                {listing.images.map((img) => (
                                    <button
                                        key={img.id}
                                        onClick={() => setSelectedImage(img.url)}
                                        className={`w-20 h-16 rounded-xl overflow-hidden shrink-0 border-2 transition ${
                                            selectedImage === img.url
                                                ? 'border-emerald-500 ring-2 ring-emerald-500/20'
                                                : 'border-transparent opacity-70 hover:opacity-100'
                                        }`}
                                    >
                                        <img src={img.url} alt="" className="w-full h-full object-cover" />
                                    </button>
                                ))}
                            </div>
                        )}

                        {/* Title, City, Price */}
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-6">
                            <div className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <h1 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                        {listing.title}
                                    </h1>
                                    <div className="flex items-center gap-1 bg-amber-50 text-amber-800 text-xs font-bold px-2 py-0.5 rounded-lg border border-amber-200">
                                        <Star className="w-3.5 h-3.5 fill-amber-400 text-amber-500" />
                                        <span>{avgRating} ({reviews.length})</span>
                                    </div>
                                </div>
                                <div className="flex items-center gap-4 text-xs text-slate-500">
                                    <span className="flex items-center gap-1">
                                        <MapPin className="w-3.5 h-3.5 text-slate-400" />
                                        {listing.city} {listing.address ? `— ${listing.address}` : ''}
                                    </span>
                                    {listing.year && (
                                        <span className="flex items-center gap-1">
                                            <Calendar className="w-3.5 h-3.5 text-slate-400" />
                                            Year {listing.year}
                                        </span>
                                    )}
                                </div>
                            </div>
                            <div className="text-left sm:text-right">
                                <span className="text-xs text-slate-400 block font-semibold">Price in ETB</span>
                                <span className="text-2xl sm:text-3xl font-black text-emerald-600 tracking-tight">
                                    ETB {Number(listing.price).toLocaleString()}
                                </span>
                            </div>
                        </div>

                        {/* Dynamic Attributes Grid */}
                        {listing.listing_attributes && Object.keys(listing.listing_attributes).length > 0 && (
                            <div className="space-y-3">
                                <h2 className="text-xs font-bold uppercase tracking-wider text-slate-400">Specifications</h2>
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                    {Object.entries(listing.listing_attributes).map(([key, val]) => {
                                        if (val === null || val === undefined || val === '') return null;
                                        const formattedKey = key.replace(/_/g, ' ');
                                        const formattedValue = typeof val === 'boolean' ? (val ? 'Yes' : 'No') : String(val);

                                        return (
                                            <div key={key} className="bg-white p-3 rounded-xl border border-slate-200/60">
                                                <span className="text-slate-400 block text-[11px]">{formattedKey}</span>
                                                <span className="font-bold text-slate-800 capitalize">{formattedValue}</span>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

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
                                                    <span title="Verified Seller" className="inline-flex shrink-0">
                                                        <ShieldCheck className="w-4 h-4 text-emerald-600" />
                                                    </span>
                                                )}
                                            </div>
                                            <p className="text-xs text-slate-500">{listing.seller?.city || 'Addis Ababa'}</p>
                                        </div>
                                    </div>
                                    <p className="text-xs text-slate-600 mb-4 leading-relaxed">
                                        Verified seller on Zacma. Connect directly via in-app chat or submit an official inquiry.
                                    </p>
                                </div>

                                <div className="space-y-2">
                                    {onStartChat && !isOwnListing && (
                                        <button
                                            onClick={() => {
                                                onClose();
                                                onStartChat(listing.user_id, listing.id);
                                            }}
                                            className="w-full bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold py-2.5 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm"
                                        >
                                            <MessageSquare className="w-3.5 h-3.5" />
                                            <span>Message Seller Directly</span>
                                        </button>
                                    )}

                                    {listing.seller?.username && (
                                        <button
                                            onClick={() => onViewProfile(listing.seller!.username!)}
                                            className="w-full text-center bg-white hover:bg-slate-100 text-slate-800 text-xs font-bold py-2.5 rounded-xl border border-slate-200 transition"
                                        >
                                            View Seller Profile & Inventory →
                                        </button>
                                    )}
                                </div>
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

                        {/* Reviews & Ratings Section */}
                        <div className="border-t border-slate-100 pt-6 space-y-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <Star className="w-4 h-4 fill-amber-400 text-amber-500" />
                                    <span>Verified Buyer Reviews ({reviews.length})</span>
                                </h3>
                                <span className="text-xs font-bold text-slate-500">Average: {avgRating} / 5.0</span>
                            </div>

                            {/* Review Form */}
                            {currentUser && !isOwnListing && (
                                <form onSubmit={handleReviewSubmit} className="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3 text-xs">
                                    <div className="flex items-center justify-between">
                                        <span className="font-bold text-slate-700">Write a Review for this Seller</span>
                                        <div className="flex items-center gap-1">
                                            {[1, 2, 3, 4, 5].map((star) => (
                                                <button
                                                    key={star}
                                                    type="button"
                                                    onClick={() => setReviewRating(star)}
                                                    className="focus:outline-none"
                                                >
                                                    <Star
                                                        className={`w-4 h-4 ${
                                                            star <= reviewRating
                                                                ? 'fill-amber-400 text-amber-500'
                                                                : 'text-slate-300'
                                                        }`}
                                                    />
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                    <textarea
                                        rows={2}
                                        value={reviewComment}
                                        onChange={(e) => setReviewComment(e.target.value)}
                                        placeholder="Share your experience inspecting or communicating with this seller..."
                                        className="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                    ></textarea>
                                    <button
                                        type="submit"
                                        disabled={submittingReview}
                                        className="bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-4 py-2 rounded-xl transition shadow-xs disabled:opacity-50"
                                    >
                                        {submittingReview ? 'Submitting...' : 'Post Review'}
                                    </button>
                                    {reviewSuccess && (
                                        <span className="text-xs text-emerald-600 font-bold ml-3">Review posted successfully!</span>
                                    )}
                                </form>
                            )}

                            {/* Reviews Feed */}
                            <div className="space-y-2">
                                {reviews.length === 0 ? (
                                    <p className="text-xs text-slate-400">No reviews posted yet for this listing.</p>
                                ) : (
                                    reviews.map((rev, idx) => (
                                        <div key={idx} className="bg-slate-50 p-3 rounded-xl border border-slate-100 text-xs space-y-1">
                                            <div className="flex items-center justify-between">
                                                <span className="font-bold text-slate-800">{rev.user?.name || 'Customer'}</span>
                                                <div className="flex items-center gap-0.5">
                                                    {[1, 2, 3, 4, 5].map((s) => (
                                                        <Star
                                                            key={s}
                                                            className={`w-3 h-3 ${
                                                                s <= rev.rating ? 'fill-amber-400 text-amber-500' : 'text-slate-200'
                                                            }`}
                                                        />
                                                    ))}
                                                </div>
                                            </div>
                                            {rev.comment && <p className="text-slate-600">{rev.comment}</p>}
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};
