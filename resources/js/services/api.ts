import axios from 'axios';
import { Category, CrmLead, Listing, ListingStatus, PaymentTransaction, SubscriptionPlan, User } from '../types/marketplace';

const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

const getAuthToken = () => {
    return localStorage.getItem('zacma_auth_token') || '';
};

export const apiClient = axios.create({
    baseURL: '/api',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    },
});

apiClient.interceptors.request.use((config) => {
    config.headers['X-CSRF-TOKEN'] = getCsrfToken();
    const token = getAuthToken();
    if (token) {
        config.headers['Authorization'] = `Bearer ${token}`;
    }
    return config;
});

export const api = {
    // Auth
    async login(login: string, password: string) {
        const res = await apiClient.post('/auth/login', { login, password });
        if (res.data.token) {
            localStorage.setItem('zacma_auth_token', res.data.token);
        }
        return res.data;
    },

    async register(data: { name: string; email: string; phone?: string; password: string; password_confirmation: string }) {
        const res = await apiClient.post('/auth/register', data);
        if (res.data.token) {
            localStorage.setItem('zacma_auth_token', res.data.token);
        }
        return res.data;
    },

    async logout() {
        try {
            await apiClient.post('/auth/logout');
        } finally {
            localStorage.removeItem('zacma_auth_token');
        }
    },

    async getMe(): Promise<User> {
        const res = await apiClient.get('/me');
        return res.data.data;
    },

    async updateProfile(formData: FormData) {
        const res = await apiClient.post('/profile', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        return res.data;
    },

    async getPublicProfile(username: string) {
        const res = await apiClient.get(`/profile/${username}`);
        return res.data.data;
    },

    // Marketplace Browse
    async getListings(params: Record<string, any> = {}) {
        const res = await apiClient.get('/marketplace/listings', { params });
        return res.data.data;
    },

    async getListing(slug: string): Promise<Listing> {
        const res = await apiClient.get(`/marketplace/listings/${slug}`);
        return res.data.data;
    },

    async getCategories(type?: string): Promise<Category[]> {
        const res = await apiClient.get('/marketplace/categories', { params: { type } });
        return res.data.data;
    },

    async getCities(): Promise<string[]> {
        const res = await apiClient.get('/marketplace/cities');
        return res.data.data;
    },

    // Inquiries
    async sendInquiry(data: { listing_id: number; name: string; email: string; phone?: string; message: string }) {
        const res = await apiClient.post('/inquiries', data);
        return res.data;
    },

    // My Listings
    async getMyListings(params: Record<string, any> = {}) {
        const res = await apiClient.get('/my-listings', { params });
        return res.data;
    },

    async createListing(formData: FormData) {
        const res = await apiClient.post('/listings', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        return res.data;
    },

    async updateListing(id: number, formData: FormData) {
        formData.append('_method', 'PUT');
        const res = await apiClient.post(`/listings/${id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        return res.data;
    },

    async deleteListing(id: number) {
        const res = await apiClient.delete(`/listings/${id}`);
        return res.data;
    },

    async changeListingStatus(id: number, status: string) {
        const res = await apiClient.post(`/listings/${id}/status`, { status });
        return res.data;
    },

    // CRM Leads
    async getCrmLeads(params: Record<string, any> = {}) {
        const res = await apiClient.get('/crm/leads', { params });
        return res.data;
    },

    async updateLeadStatus(id: number, status: string) {
        const res = await apiClient.patch(`/crm/leads/${id}/status`, { status });
        return res.data;
    },

    async deleteLead(id: number) {
        const res = await apiClient.delete(`/crm/leads/${id}`);
        return res.data;
    },

    // Favorites
    async getFavorites() {
        const res = await apiClient.get('/favorites');
        return res.data.data;
    },

    async toggleFavorite(listingId: number) {
        const res = await apiClient.post(`/favorites/${listingId}/toggle`);
        return res.data;
    },

    // Subscriptions & Payments
    async getPlans() {
        const res = await apiClient.get('/plans');
        return res.data;
    },

    async checkoutSubscription(planId: number, returnUrl?: string, gateway: string = 'chapa', billingCycle: string = 'monthly') {
        const res = await apiClient.post('/subscriptions/checkout', {
            plan_id: planId,
            return_url: returnUrl,
            gateway,
            billing_cycle: billingCycle,
        });
        return res.data;
    },

    async getPaymentHistory() {
        const res = await apiClient.get('/payments/history');
        return res.data.data;
    },

    // Reviews & Ratings
    async getListingReviews(listingId: number) {
        const res = await apiClient.get(`/listings/${listingId}/reviews`);
        return res.data;
    },

    async submitReview(listingId: number, data: { rating: number; comment?: string }) {
        const res = await apiClient.post(`/listings/${listingId}/reviews`, data);
        return res.data;
    },

    // Buyer Requirements (CRM)
    async getBuyerRequirements(params: Record<string, any> = {}) {
        const res = await apiClient.get('/buyer-requirements', { params });
        return res.data.data;
    },

    async createBuyerRequirement(data: Record<string, any>) {
        const res = await apiClient.post('/buyer-requirements', data);
        return res.data;
    },

    async getMyBuyerRequirements() {
        const res = await apiClient.get('/my-buyer-requirements');
        return res.data.data;
    },

    // In-App Messaging
    async getConversations() {
        const res = await apiClient.get('/messages/conversations');
        return res.data.data;
    },

    async getMessageThread(userId: number) {
        const res = await apiClient.get(`/messages/thread/${userId}`);
        return res.data.data;
    },

    async sendMessage(recipientId: number, message: string, listingId?: number) {
        const res = await apiClient.post('/messages/send', {
            recipient_id: recipientId,
            message,
            listing_id: listingId,
        });
        return res.data;
    },

    // Admin
    async getAdminDashboard() {
        const res = await apiClient.get('/admin/dashboard');
        return res.data.data;
    },

    async getAdminUsers(params: Record<string, any> = {}) {
        const res = await apiClient.get('/admin/users', { params });
        return res.data.data;
    },

    async toggleUserStatus(userId: number) {
        const res = await apiClient.post(`/admin/users/${userId}/toggle-status`);
        return res.data;
    },

    async getPendingListings(params: Record<string, any> = {}) {
        const res = await apiClient.get('/admin/pending-listings', { params });
        return res.data.data;
    },

    async approveListing(id: number) {
        const res = await apiClient.post(`/admin/listings/${id}/approve`);
        return res.data;
    },

    async rejectListing(id: number, reason: string) {
        const res = await apiClient.post(`/admin/listings/${id}/reject`, { reason });
        return res.data;
    },

    async getAdminCategories() {
        const res = await apiClient.get('/admin/categories');
        return res.data.data;
    },

    async createCategory(data: { name: string; type: string; icon?: string }) {
        const res = await apiClient.post('/admin/categories', data);
        return res.data;
    },

    async getAdminPlans() {
        const res = await apiClient.get('/admin/plans');
        return res.data.data;
    },

    async updateAdminPlan(planId: number, data: { name?: string; price?: number; listing_limit?: number }) {
        const res = await apiClient.post(`/admin/plans/${planId}`, data);
        return res.data;
    },

    async getAdminTransactions() {
        const res = await apiClient.get('/admin/transactions');
        return res.data.data;
    },

    async getGatewaySettings() {
        const res = await apiClient.get('/admin/gateway-settings');
        return res.data.data;
    },

    async updateGatewaySettings(data: { enabled: boolean; mode: string; public_key?: string; secret_key?: string; webhook_secret?: string }) {
        const res = await apiClient.post('/admin/gateway-settings', data);
        return res.data;
    },

    // System-Wide Knowledge-Based AI Assistant
    async chatWithAi(message: string, activeTab?: string, pageContext?: string) {
        const res = await apiClient.post('/ai/chat', {
            message,
            active_tab: activeTab,
            page_context: pageContext,
        });
        return res.data;
    },

    async getAiHistory(activeTab?: string) {
        const res = await apiClient.get('/ai/history', {
            params: { active_tab: activeTab }
        });
        return res.data;
    },

    async clearAiHistory() {
        const res = await apiClient.post('/ai/clear');
        return res.data;
    },
};

