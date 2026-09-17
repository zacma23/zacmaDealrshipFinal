<?php

namespace App\Services\AI\Knowledge;

class SubscriptionsAndPaymentsKnowledge
{
    public static function getKnowledge(): string
    {
        return <<<TEXT
### 6. SUBSCRIPTIONS, QUOTAS & ETHIOPIAN PAYMENT GATEWAYS

#### A. Subscription Tiers & Listing Quotas
1. **Basic Plan (Free / 0 ETB)**:
   - **Quota**: Up to 20 active published listings.
   - **Features**: Standard marketplace search, inbound CRM leads pipeline, standard messaging.
2. **Premium Plan (499 ETB / month)**:
   - **Quarterly**: 1,299 ETB (10% discount).
   - **Yearly**: 4,499 ETB (2+ months free).
   - **Quota**: Up to 50 active published listings.
   - **Features**: Priority marketplace search placement, verified badge eligibility, priority lead routing.
3. **Pro Plan (1,499 ETB / month)**:
   - **Quarterly**: 3,999 ETB (10% discount).
   - **Yearly**: 13,499 ETB (2+ months free).
   - **Quota**: Up to 100 active published listings.
   - **Features**: Featured homepage badge, instant moderation approval priority, full CRM suite, dedicated account support.

#### B. Hard Quota Enforcement Rule
- Listing quotas are enforced at the service level (`ListingService`).
- If a user has reached their quota limit (e.g. 20/20 on Basic), attempting to create another listing is strictly blocked with a clear message:
  *"You have reached your limit of X active listings on the Y plan. Please upgrade your subscription to post more."*
- Users can view their current usage in the user profile menu and on the "Plans & Pricing" page.

#### C. Supported Ethiopian Payment Gateways (ETB)
The platform operates a pluggable `PaymentGatewayInterface` supporting domestic Ethiopian payment providers:
1. **Chapa**: Seamless Ethiopian checkout supporting local debit cards, CBE, Awash, Dashen, and Amole.
2. **Telebirr**: Direct mobile wallet payments via Ethio Telecom.
3. **CBE Birr**: Commercial Bank of Ethiopia mobile money integration.
4. **eBirr**: Cooperative Bank / mobile wallet banking.
5. **SantimPay**: Ethiopian QR, mobile debit, and unified gateway payments.

#### D. Security, Webhooks & Idempotency
- All gateway checkouts generate a unique cryptographic `transaction_reference`.
- Webhook endpoints verify digital HMAC signatures.
- Replay-proof transaction idempotency ensures no payment can be double-processed.
TEXT;
    }
}
