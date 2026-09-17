import React, { useState, useEffect } from 'react';
import { Category, Listing, ListingType, User } from '../types/marketplace';
import { api } from '../services/api';
import { 
    X, Plus, Upload, AlertCircle, CheckCircle2, 
    Car, Home, Building2, Sparkles, ChevronRight, Layers
} from 'lucide-react';

interface AddListingModalProps {
    user: User | null;
    onClose: () => void;
    onListingCreated: () => void;
    onNavigatePricing: () => void;
    onRequireLogin: () => void;
}

export const AddListingModal: React.FC<AddListingModalProps> = ({
    user,
    onClose,
    onListingCreated,
    onNavigatePricing,
    onRequireLogin,
}) => {
    if (!user) {
        return (
            <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
                <div className="bg-white rounded-3xl max-w-md w-full p-6 text-center space-y-4 shadow-2xl border border-slate-200">
                    <div className="w-12 h-12 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center mx-auto">
                        <Sparkles className="w-6 h-6" />
                    </div>
                    <h2 className="text-lg font-bold text-slate-900">Sign In to Post a Listing</h2>
                    <p className="text-xs text-slate-500 leading-relaxed">
                        Every Zacma user account can both buy and sell. Please log in or register a free account to list your vehicle, real estate, or apartment.
                    </p>
                    <div className="flex gap-2 pt-2">
                        <button
                            onClick={onClose}
                            className="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold py-2.5 rounded-xl transition"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={() => { onClose(); onRequireLogin(); }}
                            className="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2.5 rounded-xl transition"
                        >
                            Sign In / Register
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    const quota = user.quota;
    const isQuotaExceeded = quota && quota.used >= quota.limit && !user.is_super_admin;

    const [categories, setCategories] = useState<Category[]>([]);
    const [cities, setCities] = useState<string[]>([]);

    // Form fields
    const [type, setType] = useState<ListingType>('vehicle');
    const [categoryId, setCategoryId] = useState('');
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [price, setPrice] = useState('');
    const [city, setCity] = useState('Addis Ababa');
    const [address, setAddress] = useState('');
    const [year, setYear] = useState('');
    const [bedrooms, setBedrooms] = useState('');

    // Type-specific attributes
    const [attributes, setAttributes] = useState<Record<string, any>>({
        brand: 'Toyota',
        transmission: 'Automatic',
        fuel_type: 'Petrol',
        condition: 'Local Used',
        property_purpose: 'sale',
        furnished: 'yes',
        rent_period: 'monthly',
    });

    const [imageUrls, setImageUrls] = useState<string[]>([
        'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=800&auto=format&fit=crop&q=70',
    ]);
    const [imageFiles, setImageFiles] = useState<File[]>([]);
    const [submitStatus, setSubmitStatus] = useState<'draft' | 'pending'>('pending');

    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        Promise.all([
            api.getCategories(type),
            api.getCities(),
        ]).then(([cats, cty]) => {
            setCategories(cats);
            if (cats.length > 0) {
                setCategoryId(String(cats[0].id));
            }
            setCities(cty);
        });
    }, [type]);

    const handleAttrChange = (key: string, val: any) => {
        setAttributes(prev => ({ ...prev, [key]: val }));
    };

    const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            setImageFiles(Array.from(e.target.files));
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (isQuotaExceeded) return;

        setLoading(true);
        setError(null);

        try {
            const formData = new FormData();
            formData.append('type', type);
            formData.append('category_id', categoryId);
            formData.append('title', title);
            formData.append('description', description);
            formData.append('price', price);
            formData.append('currency', 'ETB');
            formData.append('city', city);
            formData.append('address', address);
            formData.append('status', submitStatus);

            if (year) formData.append('year', year);
            if (bedrooms) formData.append('bedrooms', bedrooms);

            // Append attributes
            Object.entries(attributes).forEach(([k, v]) => {
                formData.append(`listing_attributes[${k}]`, String(v));
            });

            // Upload files or URLs
            if (imageFiles.length > 0) {
                imageFiles.forEach(file => {
                    formData.append('images[]', file);
                });
            } else if (imageUrls.length > 0) {
                imageUrls.forEach(url => {
                    formData.append('image_urls[]', url);
                });
            }

            await api.createListing(formData);
            onListingCreated();
            onClose();
        } catch (err: any) {
            console.error('Create listing error', err);
            setError(err.response?.data?.message || 'Failed to create listing.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 animate-in fade-in duration-150">
            <div className="bg-white rounded-3xl max-w-2xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200">
                {/* Header */}
                <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <div>
                        <h2 className="text-base font-black text-slate-900">Add New Marketplace Listing</h2>
                        <p className="text-[11px] text-slate-500">Universal seller flow (Vehicles, Real Estate, Apartments)</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="p-2 rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Quota Warning Banner */}
                {isQuotaExceeded && (
                    <div className="bg-amber-50 border-b border-amber-200 p-4 px-6 flex items-start gap-3">
                        <AlertCircle className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                        <div className="text-xs space-y-1">
                            <p className="font-bold text-amber-900">
                                Listing Limit Reached ({quota?.used} / {quota?.limit} on {quota?.plan_name || 'Basic'} Plan)
                            </p>
                            <p className="text-amber-700">
                                You have reached your monthly listing quota. Upgrade to Premium (50 listings) or Pro (100 listings) to publish more listings.
                            </p>
                            <button
                                type="button"
                                onClick={() => { onClose(); onNavigatePricing(); }}
                                className="inline-flex items-center gap-1 font-bold text-emerald-700 hover:underline pt-1"
                            >
                                <span>Upgrade Plan with Chapa</span>
                                <ChevronRight className="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                )}

                {/* Body */}
                <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto p-6 space-y-5 text-xs">
                    {error && (
                        <div className="bg-rose-50 border border-rose-200 text-rose-700 p-3 rounded-xl text-xs flex items-center gap-2">
                            <AlertCircle className="w-4 h-4 shrink-0" />
                            <span>{error}</span>
                        </div>
                    )}

                    {/* Step 1: Listing Type */}
                    <div>
                        <label className="block text-slate-700 font-bold mb-2">Select Listing Type</label>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <button
                                type="button"
                                onClick={() => setType('vehicle')}
                                className={`p-3 rounded-2xl border flex flex-col items-center gap-1.5 transition text-center ${
                                    type === 'vehicle'
                                        ? 'border-emerald-500 bg-emerald-50 text-emerald-800 font-bold ring-2 ring-emerald-500/20'
                                        : 'border-slate-200 hover:bg-slate-50 text-slate-600'
                                }`}
                            >
                                <Car className="w-5 h-5" />
                                <span className="text-xs">Vehicle</span>
                            </button>

                            <button
                                type="button"
                                onClick={() => setType('real_estate')}
                                className={`p-3 rounded-2xl border flex flex-col items-center gap-1.5 transition text-center ${
                                    type === 'real_estate'
                                        ? 'border-emerald-500 bg-emerald-50 text-emerald-800 font-bold ring-2 ring-emerald-500/20'
                                        : 'border-slate-200 hover:bg-slate-50 text-slate-600'
                                }`}
                            >
                                <Home className="w-5 h-5" />
                                <span className="text-xs">Real Estate</span>
                            </button>

                            <button
                                type="button"
                                onClick={() => setType('apartment')}
                                className={`p-3 rounded-2xl border flex flex-col items-center gap-1.5 transition text-center ${
                                    type === 'apartment'
                                        ? 'border-emerald-500 bg-emerald-50 text-emerald-800 font-bold ring-2 ring-emerald-500/20'
                                        : 'border-slate-200 hover:bg-slate-50 text-slate-600'
                                }`}
                            >
                                <Building2 className="w-5 h-5" />
                                <span className="text-xs">Apartment</span>
                            </button>

                            <button
                                type="button"
                                onClick={() => setType('product')}
                                className={`p-3 rounded-2xl border flex flex-col items-center gap-1.5 transition text-center ${
                                    type === 'product'
                                        ? 'border-emerald-500 bg-emerald-50 text-emerald-800 font-bold ring-2 ring-emerald-500/20'
                                        : 'border-slate-200 hover:bg-slate-50 text-slate-600'
                                }`}
                            >
                                <Layers className="w-5 h-5" />
                                <span className="text-xs">Product</span>
                            </button>
                        </div>
                    </div>

                    {/* Category Selection */}
                    <div>
                        <label className="block text-slate-700 font-bold mb-1">Category</label>
                        <select
                            required
                            value={categoryId}
                            onChange={(e) => setCategoryId(e.target.value)}
                            className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                            {categories.map((cat) => (
                                <option key={cat.id} value={cat.id}>{cat.name}</option>
                            ))}
                        </select>
                    </div>

                    {/* Title */}
                    <div>
                        <label className="block text-slate-700 font-bold mb-1">Listing Title</label>
                        <input
                            type="text"
                            required
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                            placeholder={
                                type === 'vehicle' 
                                    ? 'e.g. Toyota Corolla Executive 2021 Automatic'
                                    : type === 'apartment'
                                    ? 'e.g. Modern Furnished 2-Bedroom in Bole Atlas'
                                    : 'e.g. Luxury 4-Bedroom Villa with Compound in CMC'
                            }
                            className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        />
                    </div>

                    {/* Price in ETB & Location */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label className="block text-slate-700 font-bold mb-1">Price (Ethiopian Birr - ETB)</label>
                            <input
                                type="number"
                                required
                                min="0"
                                value={price}
                                onChange={(e) => setPrice(e.target.value)}
                                placeholder="e.g. 3850000"
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label className="block text-slate-700 font-bold mb-1">Ethiopian City / Region</label>
                            <select
                                required
                                value={city}
                                onChange={(e) => setCity(e.target.value)}
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            >
                                {cities.map(c => (
                                    <option key={c} value={c}>{c}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div>
                        <label className="block text-slate-700 font-bold mb-1">Neighborhood / Specific Address (Optional)</label>
                        <input
                            type="text"
                            value={address}
                            onChange={(e) => setAddress(e.target.value)}
                            placeholder="e.g. Bole Medhanialem, Near Edna Mall"
                            className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        />
                    </div>

                    {/* Dynamic Specifications */}
                    <div className="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
                        <span className="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            {type.replace('_', ' ')} Specifications
                        </span>

                        {type === 'vehicle' && (
                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Brand</label>
                                    <input
                                        type="text"
                                        value={attributes.brand || ''}
                                        onChange={(e) => handleAttrChange('brand', e.target.value)}
                                        placeholder="e.g. Toyota"
                                        className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs"
                                    />
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Year</label>
                                    <input
                                        type="number"
                                        value={year}
                                        onChange={(e) => setYear(e.target.value)}
                                        placeholder="e.g. 2021"
                                        className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs"
                                    />
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Transmission</label>
                                    <select
                                        value={attributes.transmission || 'Automatic'}
                                        onChange={(e) => handleAttrChange('transmission', e.target.value)}
                                        className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs"
                                    >
                                        <option value="Automatic">Automatic</option>
                                        <option value="Manual">Manual</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Fuel Type</label>
                                    <select
                                        value={attributes.fuel_type || 'Petrol'}
                                        onChange={(e) => handleAttrChange('fuel_type', e.target.value)}
                                        className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs"
                                    >
                                        <option value="Petrol">Petrol</option>
                                        <option value="Diesel">Diesel</option>
                                        <option value="Hybrid">Hybrid</option>
                                        <option value="Electric">Electric</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Mileage (km)</label>
                                    <input
                                        type="number"
                                        value={attributes.mileage || ''}
                                        onChange={(e) => handleAttrChange('mileage', e.target.value)}
                                        placeholder="e.g. 45000"
                                        className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs"
                                    />
                                </div>
                            </div>
                        )}

                        {(type === 'real_estate' || type === 'apartment') && (
                            <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Bedrooms</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={bedrooms}
                                        onChange={(e) => setBedrooms(e.target.value)}
                                        placeholder="e.g. 3"
                                        className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs"
                                    />
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Bathrooms</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={attributes.bathrooms || ''}
                                        onChange={(e) => handleAttrChange('bathrooms', e.target.value)}
                                        placeholder="e.g. 2"
                                        className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs"
                                    />
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Purpose</label>
                                    <select
                                        value={attributes.property_purpose || 'sale'}
                                        onChange={(e) => handleAttrChange('property_purpose', e.target.value)}
                                        className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs"
                                    >
                                        <option value="sale">For Sale</option>
                                        <option value="rent">For Rent</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Furnishing</label>
                                    <select
                                        value={attributes.furnished || 'no'}
                                        onChange={(e) => handleAttrChange('furnished', e.target.value)}
                                        className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs"
                                    >
                                        <option value="yes">Furnished</option>
                                        <option value="no">Unfurnished</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Rent Period</label>
                                    <select
                                        value={attributes.rent_period || 'monthly'}
                                        onChange={(e) => handleAttrChange('rent_period', e.target.value)}
                                        className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs"
                                    >
                                        <option value="monthly">Monthly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                            </div>
                        )}

                        {type === 'product' && (
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Brand / Maker</label>
                                    <input
                                        type="text"
                                        value={attributes.brand || ''}
                                        onChange={(e) => handleAttrChange('brand', e.target.value)}
                                        placeholder="e.g. Toyota, CAT, Sony"
                                        className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs"
                                    />
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Condition</label>
                                    <select
                                        value={attributes.condition || 'Brand New'}
                                        onChange={(e) => handleAttrChange('condition', e.target.value)}
                                        className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs"
                                    >
                                        <option value="Brand New">Brand New</option>
                                        <option value="Like New">Like New</option>
                                        <option value="Used">Used</option>
                                        <option value="Refurbished">Refurbished</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Part / Model #</label>
                                    <input
                                        type="text"
                                        value={attributes.part_number || ''}
                                        onChange={(e) => handleAttrChange('part_number', e.target.value)}
                                        placeholder="e.g. OEM-9982"
                                        className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs"
                                    />
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Warranty</label>
                                    <input
                                        type="text"
                                        value={attributes.warranty || ''}
                                        onChange={(e) => handleAttrChange('warranty', e.target.value)}
                                        placeholder="e.g. 6 Months"
                                        className="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs"
                                    />
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Description */}
                    <div>
                        <label className="block text-slate-700 font-bold mb-1">Description</label>
                        <textarea
                            rows={3}
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            placeholder="Describe features, condition, maintenance history, or viewings..."
                            className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none resize-none"
                        ></textarea>
                    </div>

                    {/* Image Upload */}
                    <div>
                        <label className="block text-slate-700 font-bold mb-1">Photos</label>
                        <input
                            type="file"
                            multiple
                            accept="image/*"
                            onChange={handleImageChange}
                            className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                        />
                        <p className="text-[10px] text-slate-400 mt-1">Supports JPG, PNG, WEBP up to 10MB per image.</p>
                    </div>

                    {/* Submission Action */}
                    <div className="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <label className="flex items-center gap-1.5 cursor-pointer">
                                <input
                                    type="radio"
                                    name="status"
                                    checked={submitStatus === 'pending'}
                                    onChange={() => setSubmitStatus('pending')}
                                    className="text-emerald-600 focus:ring-0"
                                />
                                <span className="font-semibold text-slate-700">Submit for Approval</span>
                            </label>
                            <label className="flex items-center gap-1.5 cursor-pointer ml-3">
                                <input
                                    type="radio"
                                    name="status"
                                    checked={submitStatus === 'draft'}
                                    onChange={() => setSubmitStatus('draft')}
                                    className="text-emerald-600 focus:ring-0"
                                />
                                <span className="font-semibold text-slate-700">Save as Draft</span>
                            </label>
                        </div>

                        <div className="flex gap-2">
                            <button
                                type="button"
                                onClick={onClose}
                                className="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-bold transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={loading || isQuotaExceeded}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-2 rounded-xl shadow-sm shadow-emerald-600/20 disabled:opacity-50 transition"
                            >
                                {loading ? 'Saving...' : submitStatus === 'pending' ? 'Submit Listing' : 'Save Draft'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
};

