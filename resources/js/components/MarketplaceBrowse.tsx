import React, { useState, useEffect } from 'react';
import { Category, Listing, ListingType } from '../types/marketplace';
import { api } from '../services/api';
import { ListingCard } from './ListingCard';
import { 
    Search, Filter, SlidersHorizontal, RotateCcw, 
    Car, Home, Building2, Layers, Sparkles, ChevronLeft, ChevronRight
} from 'lucide-react';

interface MarketplaceBrowseProps {
    onSelectListing: (listing: Listing) => void;
    onToggleFavorite: (listingId: number) => void;
}

export const MarketplaceBrowse: React.FC<MarketplaceBrowseProps> = ({
    onSelectListing,
    onToggleFavorite,
}) => {
    const [listings, setListings] = useState<Listing[]>([]);
    const [categories, setCategories] = useState<Category[]>([]);
    const [cities, setCities] = useState<string[]>([]);
    const [loading, setLoading] = useState(true);

    // Filters state
    const [type, setType] = useState<string>('');
    const [search, setSearch] = useState('');
    const [city, setCity] = useState('');
    const [categoryId, setCategoryId] = useState('');
    const [minPrice, setMinPrice] = useState('');
    const [maxPrice, setMaxPrice] = useState('');
    const [sort, setSort] = useState('newest');

    // Type-specific filters
    const [brand, setBrand] = useState('');
    const [year, setYear] = useState('');
    const [transmission, setTransmission] = useState('');
    const [fuelType, setFuelType] = useState('');
    const [bedrooms, setBedrooms] = useState('');
    const [propertyPurpose, setPropertyPurpose] = useState('');
    const [furnished, setFurnished] = useState('');
    const [rentPeriod, setRentPeriod] = useState('');

    // Pagination
    const [currentPage, setCurrentPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);

    const [filterDrawerOpen, setFilterDrawerOpen] = useState(false);

    // Fetch initial data
    useEffect(() => {
        Promise.all([
            api.getCategories(),
            api.getCities(),
        ]).then(([cats, cty]) => {
            setCategories(cats);
            setCities(cty);
        }).catch(err => console.error('Failed to load metadata', err));
    }, []);

    // Fetch listings when filters change
    const fetchListingsData = async () => {
        setLoading(true);
        try {
            const params: Record<string, any> = {
                page: currentPage,
                type: type || undefined,
                search: search || undefined,
                city: city || undefined,
                category_id: categoryId || undefined,
                min_price: minPrice || undefined,
                max_price: maxPrice || undefined,
                sort: sort || undefined,
            };

            if (type === 'vehicle') {
                if (brand) params.brand = brand;
                if (year) params.year = year;
                if (transmission) params.transmission = transmission;
                if (fuelType) params.fuel_type = fuelType;
            } else if (type === 'real_estate' || type === 'apartment') {
                if (bedrooms) params.bedrooms = bedrooms;
                if (propertyPurpose) params.property_purpose = propertyPurpose;
                if (furnished) params.furnished = furnished;
                if (rentPeriod) params.rent_period = rentPeriod;
            }

            const data = await api.getListings(params);
            setListings(data.data || []);
            setCurrentPage(data.current_page || 1);
            setLastPage(data.last_page || 1);
            setTotal(data.total || 0);
        } catch (err) {
            console.error('Failed to fetch listings', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchListingsData();
    }, [currentPage, type, sort]);

    const handleApplyFilters = (e: React.FormEvent) => {
        e.preventDefault();
        setCurrentPage(1);
        fetchListingsData();
    };

    const handleResetFilters = () => {
        setType('');
        setSearch('');
        setCity('');
        setCategoryId('');
        setMinPrice('');
        setMaxPrice('');
        setSort('newest');
        setBrand('');
        setYear('');
        setTransmission('');
        setFuelType('');
        setBedrooms('');
        setPropertyPurpose('');
        setFurnished('');
        setRentPeriod('');
        setCurrentPage(1);
        setTimeout(() => fetchListingsData(), 0);
    };

    return (
        <div className="space-y-8 pb-16">
            {/* Hero Section */}
            <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-900 text-white p-6 sm:p-10 shadow-xl">
                <div className="relative z-10 max-w-3xl space-y-4">
                    <div className="inline-flex items-center gap-2 bg-emerald-500/20 text-emerald-300 px-3 py-1 rounded-full text-xs font-bold border border-emerald-500/30">
                        <Sparkles className="w-3.5 h-3.5" />
                        <span>Ethiopia-First Marketplace & CRM</span>
                    </div>
                    <h1 className="text-2xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                        Find Vehicles, Real Estate & Apartments Across Ethiopia
                    </h1>
                    <p className="text-slate-300 text-xs sm:text-sm max-w-2xl leading-relaxed">
                        One unified account allows every Ethiopian to browse listings, inquiry directly with owners, or list their own properties with zero middlemen.
                    </p>

                    {/* Search Bar in Hero */}
                    <form onSubmit={handleApplyFilters} className="pt-2 flex flex-col sm:flex-row gap-2 max-w-2xl">
                        <div className="relative flex-1">
                            <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input
                                type="text"
                                placeholder="Search by model, neighborhood, or keywords (e.g. Toyota, Bole, CMC)..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full bg-white/10 backdrop-blur-md border border-white/20 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white placeholder-slate-400 focus:bg-white focus:text-slate-900 focus:outline-none transition shadow-inner"
                            />
                        </div>
                        <button
                            type="submit"
                            className="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold px-6 py-2.5 rounded-xl transition text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20"
                        >
                            <Search className="w-4 h-4" />
                            <span>Search</span>
                        </button>
                    </form>
                </div>
            </div>

            {/* Type Switcher Tabs */}
            <div className="flex items-center justify-between gap-2 overflow-x-auto pb-1 border-b border-slate-200">
                <div className="flex gap-2">
                    <button
                        onClick={() => { setType(''); setCurrentPage(1); }}
                        className={`px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition ${
                            type === ''
                                ? 'bg-slate-900 text-white shadow-sm'
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'
                        }`}
                    >
                        <Layers className="w-4 h-4" />
                        <span>All Types</span>
                    </button>

                    <button
                        onClick={() => { setType('vehicle'); setCurrentPage(1); }}
                        className={`px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition ${
                            type === 'vehicle'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'
                        }`}
                    >
                        <Car className="w-4 h-4" />
                        <span>Vehicles</span>
                    </button>

                    <button
                        onClick={() => { setType('real_estate'); setCurrentPage(1); }}
                        className={`px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition ${
                            type === 'real_estate'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'
                        }`}
                    >
                        <Home className="w-4 h-4" />
                        <span>Real Estate</span>
                    </button>

                    <button
                        onClick={() => { setType('apartment'); setCurrentPage(1); }}
                        className={`px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition ${
                            type === 'apartment'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'
                        }`}
                    >
                        <Building2 className="w-4 h-4" />
                        <span>Apartments</span>
                    </button>

                    <button
                        onClick={() => { setType('product'); setCurrentPage(1); }}
                        className={`px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition ${
                            type === 'product'
                                ? 'bg-emerald-600 text-white shadow-sm'
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'
                        }`}
                    >
                        <Layers className="w-4 h-4" />
                        <span>Products</span>
                    </button>
                </div>

                <div className="flex items-center gap-2">
                    <button
                        onClick={() => setFilterDrawerOpen(!filterDrawerOpen)}
                        className={`px-3 py-2 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 ${
                            filterDrawerOpen 
                                ? 'bg-emerald-50 border-emerald-300 text-emerald-800'
                                : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'
                        }`}
                    >
                        <SlidersHorizontal className="w-3.5 h-3.5" />
                        <span>Filters</span>
                    </button>

                    <select
                        value={sort}
                        onChange={(e) => setSort(e.target.value)}
                        className="bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="newest">Newest First</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="oldest">Oldest First</option>
                    </select>
                </div>
            </div>

            {/* Filter Drawer / Panel */}
            {filterDrawerOpen && (
                <form onSubmit={handleApplyFilters} className="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-4 animate-in fade-in duration-100">
                    <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                        <span className="text-xs font-bold uppercase tracking-wider text-slate-500">Filter Listings</span>
                        <button
                            type="button"
                            onClick={handleResetFilters}
                            className="text-xs font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1"
                        >
                            <RotateCcw className="w-3 h-3" />
                            <span>Reset All</span>
                        </button>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                        {/* City Filter */}
                        <div>
                            <label className="block text-slate-600 font-semibold mb-1">City / Region</label>
                            <select
                                value={city}
                                onChange={(e) => setCity(e.target.value)}
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="">All Ethiopian Cities</option>
                                {cities.map((c) => (
                                    <option key={c} value={c}>{c}</option>
                                ))}
                            </select>
                        </div>

                        {/* Category Filter */}
                        <div>
                            <label className="block text-slate-600 font-semibold mb-1">Category</label>
                            <select
                                value={categoryId}
                                onChange={(e) => setCategoryId(e.target.value)}
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="">All Categories</option>
                                {categories
                                    .filter(c => !type || c.type === type)
                                    .map(c => (
                                        <option key={c.id} value={c.id}>{c.name}</option>
                                    ))
                                }
                            </select>
                        </div>

                        {/* Price Range */}
                        <div>
                            <label className="block text-slate-600 font-semibold mb-1">Min Price (ETB)</label>
                            <input
                                type="number"
                                placeholder="0"
                                value={minPrice}
                                onChange={(e) => setMinPrice(e.target.value)}
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                        <div>
                            <label className="block text-slate-600 font-semibold mb-1">Max Price (ETB)</label>
                            <input
                                type="number"
                                placeholder="Any"
                                value={maxPrice}
                                onChange={(e) => setMaxPrice(e.target.value)}
                                className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        {/* Vehicle specific */}
                        {type === 'vehicle' && (
                            <>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Vehicle Brand</label>
                                    <select
                                        value={brand}
                                        onChange={(e) => setBrand(e.target.value)}
                                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                                    >
                                        <option value="">All Brands</option>
                                        <option value="Toyota">Toyota</option>
                                        <option value="Hyundai">Hyundai</option>
                                        <option value="Suzuki">Suzuki</option>
                                        <option value="Isuzu">Isuzu</option>
                                        <option value="Mercedes">Mercedes-Benz</option>
                                        <option value="Ford">Ford</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Model Year</label>
                                    <input
                                        type="number"
                                        placeholder="e.g. 2021"
                                        value={year}
                                        onChange={(e) => setYear(e.target.value)}
                                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                                    />
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Transmission</label>
                                    <select
                                        value={transmission}
                                        onChange={(e) => setTransmission(e.target.value)}
                                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                                    >
                                        <option value="">Any</option>
                                        <option value="Automatic">Automatic</option>
                                        <option value="Manual">Manual</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Fuel Type</label>
                                    <select
                                        value={fuelType}
                                        onChange={(e) => setFuelType(e.target.value)}
                                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                                    >
                                        <option value="">Any</option>
                                        <option value="Petrol">Petrol (Benzine)</option>
                                        <option value="Diesel">Diesel</option>
                                        <option value="Hybrid">Hybrid</option>
                                        <option value="Electric">Electric</option>
                                    </select>
                                </div>
                            </>
                        )}

                        {/* Real Estate & Apartment specific */}
                        {(type === 'real_estate' || type === 'apartment') && (
                            <>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Min Bedrooms</label>
                                    <select
                                        value={bedrooms}
                                        onChange={(e) => setBedrooms(e.target.value)}
                                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                                    >
                                        <option value="">Any</option>
                                        <option value="1">1+ Bedrooms</option>
                                        <option value="2">2+ Bedrooms</option>
                                        <option value="3">3+ Bedrooms</option>
                                        <option value="4">4+ Bedrooms</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Purpose</label>
                                    <select
                                        value={propertyPurpose}
                                        onChange={(e) => setPropertyPurpose(e.target.value)}
                                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                                    >
                                        <option value="">For Sale & Rent</option>
                                        <option value="sale">For Sale</option>
                                        <option value="rent">For Rent</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Furnishing</label>
                                    <select
                                        value={furnished}
                                        onChange={(e) => setFurnished(e.target.value)}
                                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                                    >
                                        <option value="">Any</option>
                                        <option value="yes">Furnished</option>
                                        <option value="no">Unfurnished</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-slate-600 font-semibold mb-1">Rent Period</label>
                                    <select
                                        value={rentPeriod}
                                        onChange={(e) => setRentPeriod(e.target.value)}
                                        className="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs"
                                    >
                                        <option value="">Any</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                            </>
                        )}
                    </div>

                    <div className="flex justify-end pt-2">
                        <button
                            type="submit"
                            className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-2 rounded-xl text-xs transition"
                        >
                            Apply Filter Options
                        </button>
                    </div>
                </form>
            )}

            {/* Results Count Summary */}
            <div className="flex items-center justify-between text-xs text-slate-500 font-medium">
                <span>Showing {listings.length} of {total} verified marketplace listings</span>
                {total > 0 && <span>Page {currentPage} of {lastPage}</span>}
            </div>

            {/* Listings Grid */}
            {loading ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    {[1, 2, 3, 4, 5, 6, 7, 8].map(n => (
                        <div key={n} className="bg-white rounded-2xl border border-slate-200/80 p-4 space-y-3 animate-pulse">
                            <div className="aspect-16/10 bg-slate-200 rounded-xl"></div>
                            <div className="h-4 bg-slate-200 rounded-md w-3/4"></div>
                            <div className="h-3 bg-slate-200 rounded-md w-1/2"></div>
                            <div className="h-6 bg-slate-200 rounded-md w-1/3"></div>
                        </div>
                    ))}
                </div>
            ) : listings.length > 0 ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    {listings.map((listing) => (
                        <ListingCard
                            key={listing.id}
                            listing={listing}
                            onSelect={onSelectListing}
                            onToggleFavorite={onToggleFavorite}
                        />
                    ))}
                </div>
            ) : (
                <div className="bg-white rounded-3xl border border-slate-200/80 p-12 text-center max-w-lg mx-auto space-y-3">
                    <div className="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto text-slate-400">
                        <Search className="w-6 h-6" />
                    </div>
                    <h3 className="font-extrabold text-slate-900 text-base">No Listings Found</h3>
                    <p className="text-xs text-slate-500 leading-relaxed">
                        We couldn't find any approved listings matching your filter criteria. Try expanding your price range or changing location.
                    </p>
                    <button
                        onClick={handleResetFilters}
                        className="mt-2 bg-emerald-50 text-emerald-700 font-bold px-4 py-2 rounded-xl text-xs hover:bg-emerald-100 transition"
                    >
                        Reset All Filters
                    </button>
                </div>
            )}

            {/* Pagination Controls */}
            {lastPage > 1 && (
                <div className="flex items-center justify-center gap-2 pt-6">
                    <button
                        disabled={currentPage <= 1}
                        onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                        className="p-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-40 transition"
                    >
                        <ChevronLeft className="w-4 h-4" />
                    </button>

                    <span className="text-xs font-bold text-slate-700 px-3">
                        {currentPage} / {lastPage}
                    </span>

                    <button
                        disabled={currentPage >= lastPage}
                        onClick={() => setCurrentPage(prev => Math.min(lastPage, prev + 1))}
                        className="p-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:opacity-40 transition"
                    >
                        <ChevronRight className="w-4 h-4" />
                    </button>
                </div>
            )}
        </div>
    );
};

