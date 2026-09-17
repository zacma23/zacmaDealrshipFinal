export type ListingType = 'vehicle' | 'real_estate' | 'apartment' | 'product';

export type ListingStatus = 'draft' | 'pending' | 'published' | 'rejected' | 'sold' | 'rented' | 'expired';

export type CrmLeadStage = 'New' | 'Contacted' | 'Closed';

export interface Profile {
    id: number;
    user_id: number;
    name?: string;
    phone?: string;
    city?: string;
    bio?: string;
    photo?: string;
    photo_url?: string;
    is_verified?: boolean;
    account_type?: 'individual' | 'business' | 'dealer';
    business_name?: string;
    license_number?: string;
    tin_number?: string;
    address?: string;
    website?: string;
}

export interface User {
    id: number;
    name: string;
    username?: string;
    email: string;
    phone?: string;
    role?: string;
    avatar?: string;
    is_super_admin: boolean;
    is_active?: boolean;
    profile?: Profile;
    quota?: {
        limit: number;
        used: number;
        remaining: number;
        can_create: boolean;
        plan_name?: string;
    };
    subscription?: {
        plan_name?: string;
        ends_at?: string;
        billing_cycle?: string;
        gateway?: string;
    };
}

export interface Category {
    id: number;
    name: string;
    slug: string;
    type?: ListingType;
    icon?: string;
    is_active: boolean;
    listings_count?: number;
}

export interface ListingImage {
    id: number;
    url: string;
    is_primary: boolean;
}

export interface Listing {
    id: number;
    user_id: number;
    category_id: number;
    category_name?: string;
    type: ListingType;
    title: string;
    slug: string;
    description?: string;
    price: number;
    currency: string;
    city: string;
    address?: string;
    status: ListingStatus;
    rejection_reason?: string;
    year?: number;
    bedrooms?: number;
    listing_attributes: Record<string, any>;
    views_count: number;
    featured: boolean;
    primary_image?: string;
    images?: ListingImage[];
    seller?: {
        id: number;
        name: string;
        username?: string;
        avatar: string;
        city: string;
        is_verified: boolean;
        account_type?: string;
        business_name?: string;
    };
    is_favorited?: boolean;
    created_at: string;
}

export interface CrmLead {
    id: number;
    inquiry_id?: number;
    user_id: number;
    buyer_id?: number;
    listing_id: number;
    buyer_name: string;
    buyer_email: string;
    buyer_phone?: string;
    message: string;
    status: CrmLeadStage;
    notes?: string;
    created_at: string;
    listing?: {
        id: number;
        title: string;
        slug: string;
        price: number;
        currency: string;
        type: ListingType;
        primary_image?: string;
    };
}

export interface BuyerRequirement {
    id: number;
    user_id: number;
    user?: { id: number; name: string; email: string };
    type: ListingType;
    title: string;
    description?: string;
    city: string;
    budget_min?: number;
    budget_max?: number;
    currency: string;
    specifications?: Record<string, any>;
    status: 'open' | 'fulfilled' | 'closed';
    created_at: string;
}

export interface Review {
    id: number;
    user_id: number;
    user?: { id: number; name: string };
    seller_id: number;
    listing_id: number;
    rating: number;
    comment?: string;
    created_at: string;
}

export interface SubscriptionPlan {
    id: number;
    name: string;
    slug: string;
    price: number;
    price_quarterly?: number;
    price_yearly?: number;
    currency: string;
    listing_limit: number;
    billing_period: string;
    features: string[];
    is_current?: boolean;
}

export interface PaymentTransaction {
    id: number;
    user_id: number;
    amount: number;
    currency: string;
    provider: string;
    transaction_reference: string;
    provider_reference?: string;
    status: string;
    verified_at?: string;
    created_at: string;
    plan?: {
        name: string;
    };
    user?: {
        name: string;
        email: string;
    };
}
