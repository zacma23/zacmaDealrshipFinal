import React, { useState, useEffect } from 'react';
import { PaymentTransaction, SubscriptionPlan, User } from '../types/marketplace';
import { api } from '../services/api';
import { 
    Check, Sparkles, Shield, Zap, 
    CreditCard, ExternalLink, Clock, AlertCircle, CheckCircle2
} from 'lucide-react';

interface SubscriptionViewProps {
    user: User | null;
    onRequireLogin: () => void;
}

export const SubscriptionView: React.FC<SubscriptionViewProps> = ({
    user,
    onRequireLogin,
}) => {
    const [plans, setPlans] = useState<SubscriptionPlan[]>([]);
    const [currentSubscription, setCurrentSubscription] = useState<any>(null);
    const [payments, setPayments] = useState<PaymentTransaction[]>([]);
    const [loading, setLoading] = useState(true);
    const [checkoutLoading, setCheckoutLoading] = useState<number | null>(null);
    const [billingCycle, setBillingCycle] = useState<'monthly' | 'quarterly' | 'yearly'>('monthly');
    const [selectedGateway, setSelectedGateway] = useState<string>('chapa');
    const [message, setMessage] = useState<{ text: string; type: 'success' | 'error' } | null>(null);

    const fetchData = async () => {
        setLoading(true);
        try {
            const plansRes = await api.getPlans();
            setPlans(plansRes.data || []);
            setCurrentSubscription(plansRes.current_subscription || null);

            if (user) {
                const historyRes = await api.getPaymentHistory();
                setPayments(historyRes.data || []);
            }
        } catch (err) {
            console.error('Failed to load subscription data', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
        // Check for payment success param in URL
        const params = new URLSearchParams(window.location.search);
        if (params.get('payment') === 'success') {
            setMessage({
                text: 'Your payment was processed successfully! Your subscription and listing quota are now active.',
                type: 'success',
            });
        }
    }, [user]);

    const handleCheckout = async (planId: number) => {
        if (!user) {
            onRequireLogin();
            return;
        }

        setCheckoutLoading(planId);
        setMessage(null);

        try {
            const res = await api.checkoutSubscription(planId, undefined, selectedGateway, billingCycle);
            if (res.checkout_url) {
                window.location.href = res.checkout_url;
            } else {
                setMessage({ text: res.message || 'Plan activated.', type: 'success' });
                fetchData();
            }
        } catch (err: any) {
            setMessage({
                text: err.response?.data?.message || 'Failed to initialize payment gateway.',
                type: 'error',
            });
        } finally {
            setCheckoutLoading(null);
        }
    };

    return (
        <div className="space-y-10 pb-16">
            {/* Header */}
            <div className="text-center max-w-2xl mx-auto space-y-3">
                <div className="inline-flex items-center gap-1.5 bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full">
                    <Sparkles className="w-3.5 h-3.5" />
                    <span>Multi-Industry Dealership & SaaS Subscriptions</span>
                </div>
                <h1 className="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
                    Transparent Ethiopian SaaS Pricing
                </h1>
                <p className="text-xs sm:text-sm text-slate-500 leading-relaxed">
                    Start on the free Basic plan (20 listings). Scale to Premium or Pro for increased listing quotas, verified seller badges, and priority placement. Pay with Chapa, Telebirr, CBE Birr, eBirr, or SantimPay.
                </p>

                {/* Billing Cycle Switcher */}
                <div className="inline-flex items-center p-1 bg-slate-100 border border-slate-200 rounded-2xl gap-1 pt-1 mt-4">
                    <button
                        type="button"
                        onClick={() => setBillingCycle('monthly')}
                        className={`px-3.5 py-1.5 rounded-xl text-xs font-bold transition ${
                            billingCycle === 'monthly' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'
                        }`}
                    >
                        Monthly
                    </button>
                    <button
                        type="button"
                        onClick={() => setBillingCycle('quarterly')}
                        className={`px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 ${
                            billingCycle === 'quarterly' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'
                        }`}
                    >
                        <span>Quarterly</span>
                        <span className="bg-emerald-100 text-emerald-800 text-[10px] px-1.5 py-0.2 rounded-full">10% OFF</span>
                    </button>
                    <button
                        type="button"
                        onClick={() => setBillingCycle('yearly')}
                        className={`px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 ${
                            billingCycle === 'yearly' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'
                        }`}
                    >
                        <span>Yearly</span>
                        <span className="bg-emerald-500 text-white text-[10px] px-1.5 py-0.2 rounded-full font-black">2 MO FREE</span>
                    </button>
                </div>

                {/* Payment Gateway Selector */}
                <div className="pt-2">
                    <label className="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Select Payment Method</label>
                    <div className="flex flex-wrap items-center justify-center gap-2">
                        {[
                            { id: 'chapa', name: 'Chapa (Cards & Apps)' },
                            { id: 'telebirr', name: 'Telebirr Direct' },
                            { id: 'cbe', name: 'CBE Birr' },
                            { id: 'ebirr', name: 'eBirr' },
                            { id: 'santimpay', name: 'SantimPay' },
                        ].map(gw => (
                            <button
                                key={gw.id}
                                type="button"
                                onClick={() => setSelectedGateway(gw.id)}
                                className={`px-3 py-1.5 rounded-xl text-xs font-bold border transition ${
                                    selectedGateway === gw.id
                                        ? 'bg-emerald-50 border-emerald-500 text-emerald-800 ring-2 ring-emerald-500/20'
                                        : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                                }`}
                            >
                                {gw.name}
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            {/* Alert Message */}
            {message && (
                <div className={`max-w-2xl mx-auto p-4 rounded-2xl text-xs flex items-center gap-3 border ${
                    message.type === 'success' 
                        ? 'bg-emerald-50 border-emerald-300 text-emerald-900'
                        : 'bg-rose-50 border-rose-300 text-rose-900'
                }`}>
                    {message.type === 'success' ? (
                        <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
                    ) : (
                        <AlertCircle className="w-5 h-5 text-rose-600 shrink-0" />
                    )}
                    <span className="font-semibold">{message.text}</span>
                </div>
            )}

            {/* Pricing Cards Grid */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-6xl mx-auto">
                {plans.map(plan => {
                    const isCurrent = plan.is_current;
                    const isPremium = plan.slug === 'premium';

                    return (
                        <div
                            key={plan.id}
                            className={`rounded-3xl p-6 sm:p-8 flex flex-col justify-between transition duration-200 relative ${
                                isPremium
                                    ? 'bg-gradient-to-b from-slate-900 to-slate-950 text-white shadow-xl ring-2 ring-emerald-500'
                                    : 'bg-white text-slate-900 border border-slate-200/80 shadow-xs'
                            }`}
                        >
                            {/* Featured Ribbon */}
                            {isPremium && (
                                <div className="absolute -top-3 left-1/2 -translate-x-1/2 bg-emerald-500 text-slate-950 text-[10px] font-black uppercase tracking-wider px-3 py-0.5 rounded-full shadow-md">
                                    Most Popular
                                </div>
                            )}

                            <div>
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className={`text-lg font-black ${isPremium ? 'text-white' : 'text-slate-900'}`}>
                                        {plan.name}
                                    </h3>
                                    {isCurrent && (
                                        <span className="bg-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full border border-emerald-500/30">
                                            Current Plan
                                        </span>
                                    )}
                                </div>

                                <div className="mb-6 flex items-baseline gap-1">
                                    <span className="text-3xl sm:text-4xl font-black tracking-tight">
                                        {plan.price > 0 ? `ETB ${Number(
                                            billingCycle === 'yearly' 
                                                ? (plan.price_yearly || plan.price * 10)
                                                : billingCycle === 'quarterly'
                                                    ? (plan.price_quarterly || Math.round(plan.price * 2.7))
                                                    : plan.price
                                        ).toLocaleString()}` : 'Free'}
                                    </span>
                                    <span className={`text-xs ${isPremium ? 'text-slate-400' : 'text-slate-500'}`}>
                                        {plan.price > 0 ? (billingCycle === 'yearly' ? '/ year' : billingCycle === 'quarterly' ? '/ quarter' : '/ month') : ''}
                                    </span>
                                </div>

                                {/* Quota Highlight */}
                                <div className={`p-3 rounded-2xl mb-6 text-xs font-bold flex items-center gap-2 ${
                                    isPremium ? 'bg-white/10 text-emerald-300' : 'bg-emerald-50 text-emerald-900'
                                }`}>
                                    <Zap className="w-4 h-4 text-emerald-400 shrink-0" />
                                    <span>Quota: Up to {plan.listing_limit} active listings</span>
                                </div>

                                {/* Features List */}
                                <ul className="space-y-3 text-xs mb-8">
                                    {plan.features?.map((feat, idx) => (
                                        <li key={idx} className="flex items-center gap-2.5">
                                            <div className={`w-4 h-4 rounded-full flex items-center justify-center shrink-0 ${
                                                isPremium ? 'bg-emerald-500 text-slate-950' : 'bg-emerald-100 text-emerald-700'
                                            }`}>
                                                <Check className="w-2.5 h-2.5 stroke-3" />
                                            </div>
                                            <span className={isPremium ? 'text-slate-300' : 'text-slate-600'}>
                                                {feat}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            {/* Action Button */}
                            <div>
                                {isCurrent ? (
                                    <button
                                        disabled
                                        className={`w-full py-3 rounded-xl font-bold text-xs cursor-default ${
                                            isPremium ? 'bg-white/10 text-slate-400' : 'bg-slate-100 text-slate-400'
                                        }`}
                                    >
                                        Active Plan
                                    </button>
                                ) : (
                                    <button
                                        onClick={() => handleCheckout(plan.id)}
                                        disabled={checkoutLoading === plan.id}
                                        className={`w-full py-3 rounded-xl font-bold text-xs transition flex items-center justify-center gap-2 shadow-md ${
                                            isPremium
                                                ? 'bg-emerald-500 hover:bg-emerald-400 text-slate-950 shadow-emerald-500/20'
                                                : 'bg-slate-900 hover:bg-slate-800 text-white'
                                        }`}
                                    >
                                        {checkoutLoading === plan.id ? (
                                            <span>Redirecting to Chapa...</span>
                                        ) : (
                                            <>
                                                <span>Upgrade to {plan.name}</span>
                                                <CreditCard className="w-3.5 h-3.5" />
                                            </>
                                        )}
                                    </button>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Payment History Section (User-Facing) */}
            {user && (
                <div className="max-w-4xl mx-auto bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-4">
                    <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h3 className="font-extrabold text-slate-900 text-base">Payment History</h3>
                            <p className="text-xs text-slate-500">Your previous Chapa transaction receipts and subscription activations.</p>
                        </div>
                        <CreditCard className="w-5 h-5 text-slate-400" />
                    </div>

                    {payments.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                        <th className="py-2.5">Date</th>
                                        <th className="py-2.5">Transaction Ref</th>
                                        <th className="py-2.5">Plan</th>
                                        <th className="py-2.5">Amount</th>
                                        <th className="py-2.5">Gateway</th>
                                        <th className="py-2.5 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {payments.map(p => (
                                        <tr key={p.id} className="hover:bg-slate-50/50">
                                            <td className="py-3 text-slate-600 font-medium">
                                                {new Date(p.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="py-3 font-mono text-slate-700 font-medium">
                                                {p.transaction_reference}
                                            </td>
                                            <td className="py-3 font-bold text-slate-800">
                                                {p.plan?.name || 'Subscription'}
                                            </td>
                                            <td className="py-3 font-black text-emerald-700">
                                                ETB {Number(p.amount).toLocaleString()}
                                            </td>
                                            <td className="py-3 capitalize text-slate-600">
                                                {p.provider}
                                            </td>
                                            <td className="py-3 text-right">
                                                <span className={`inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full ${
                                                    p.status === 'completed' || p.status === 'verified'
                                                        ? 'bg-emerald-100 text-emerald-800'
                                                        : 'bg-amber-100 text-amber-800'
                                                }`}>
                                                    {p.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-xs text-slate-400 text-center py-6">
                            No billing transactions found. Upgrade a plan to see payment records here.
                        </p>
                    )}
                </div>
            )}
        </div>
    );
};

