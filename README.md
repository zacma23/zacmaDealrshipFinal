# Zacma Dealership & Multi-Industry Marketplace SaaS (Ethiopia-First)

[![CI Status](https://github.com/zacma23/zacmaDealrshipFinal/actions/workflows/ci.yml/badge.svg)](https://github.com/zacma23/zacmaDealrshipFinal/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-emerald.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![React](https://img.shields.io/badge/React-18-blue.svg)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-blue.svg)](https://www.typescriptlang.org)
[![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-4.x-cyan.svg)](https://tailwindcss.com)

A modern, production-ready Multi-Industry Dealership Marketplace, CRM, and Subscription SaaS built for Ethiopia. Designed with universal user listing capabilities, flexible JSONB specifications, automated inbound lead pipelines, Ethiopian Birr (`ETB`) pricing, and multi-gateway payment integrations (Chapa, Telebirr, CBE Birr, eBirr, SantimPay).

---

## Key Features

### 1. Universal User Listing (One Account = Buyer + Seller)
- Every authenticated user uses a single unified profile to browse, inquire, save favorites, and create listings.
- No separate seller account registration required.
- Supports individual sellers as well as verified dealer and business profiles.

### 2. Multi-Industry Marketplace
- Unified `listings` architecture supporting 4 key verticals with dynamic JSONB specifications:
  - **Vehicles**: Year, Make, Model, Transmission (Auto/Manual), Fuel Type (Petrol, Diesel, Hybrid, Electric), Mileage.
  - **Real Estate**: Property Purpose (Sale/Rent), Bedrooms, Bathrooms, Area (sqm), Furnishing.
  - **Apartments**: Floor, Bedrooms, Bathrooms, Lease Term, Deposit, Standby Generator/Elevator.
  - **General Products**: Auto Spare Parts, Heavy Equipment, Condition (New/Used), Warranty.
- Public search strictly filters published inventory with Ethiopian city filters and ETB price sliders.

### 3. Automated CRM & Lead Generation
- When a buyer submits a "Contact Seller" inquiry:
  1. An `Inquiry` is logged.
  2. A `CrmLead` is automatically generated in the seller's CRM inbox with buyer contact details.
  3. A database notification is dispatched to the seller.
- 3-stage visual pipeline: **New** → **Contacted** → **Closed** with notes and follow-up tracking.
- **Buyer Requirements Hub**: Buyers can post search requests (e.g. "Looking for 2020 Toyota RAV4 under 4.5M ETB in Addis") that sellers can review and match.

### 4. Subscription SaaS & Listing Quotas
- Hard listing quotas enforced per plan:
  - **Basic**: 20 active listings (Free on registration).
  - **Premium**: 50 active listings.
  - **Pro Enterprise**: 100 active listings.
- Multiple billing cycles: **Monthly**, **Quarterly** (10% discount), and **Yearly** (2 months free).
- Hard block on quota exceedance with clear error payloads and upgrade CTAs.

### 5. Ethiopian Payment Architecture
- Modular gateway abstraction implementing `PaymentGatewayInterface`:
  - **Chapa**: Cards, Telebirr, and CBEBirr with webhook signature verification.
  - **Telebirr Direct**: Ethio Telecom payment integration adapter.
  - **CBE Birr**: Commercial Bank of Ethiopia checkout.
  - **eBirr**: Mobile payment gateway adapter.
  - **SantimPay**: Modern multi-bank gateway adapter.
- **Replay-proof idempotency**: Webhook callbacks verify status and guarantee idempotent subscription activation.

### 6. Super Admin Command Center
- Moderation queue: Review pending listings, inspect galleries, approve or reject with mandatory reasons.
- User management: Search users, toggle active status, inspect listing counts.
- Subscription plan editor: Update prices, billing periods, and listing quotas live.
- Gateway configuration: Switch live/test modes and manage API credentials securely.

---

## Technology Stack

- **Backend**: PHP 8.2+, Laravel 12, Laravel Sanctum
- **Frontend**: React 18, TypeScript, Tailwind CSS, Lucide Icons, Vite
- **Database**: PostgreSQL 16 (production) / SQLite (local dev and testing)
- **Cache & Queue**: Redis 7
- **Containerization**: Docker, Docker Compose, Nginx, Supervisor

---

## Getting Started (Local Development)

### Prerequisites
- PHP 8.2 or higher with extensions: `pdo_sqlite`, `pdo_pgsql`, `mbstring`, `intl`, `gd`, `zip`
- Composer 2.x
- Node.js 20.x and NPM

### Installation Steps

1. **Clone the Repository**
   ```bash
   git clone https://github.com/zacma23/zacmaDealrshipFinal.git
   cd zacmaDealrshipFinal
   ```

2. **Install PHP & Node Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run Migrations & Seed Database**
   ```bash
   php artisan migrate --seed --class=MvpMarketplaceSeeder
   php artisan storage:link
   ```

5. **Build Frontend Assets**
   ```bash
   npm run build
   # Or for active local development:
   # npm run dev
   ```

6. **Serve Application**
   ```bash
   php artisan serve
   ```
   Open your browser at `http://127.0.0.1:8000`.

---

## Docker Deployment (Production)

To run the complete stack (Laravel + Nginx + Supervisor + PostgreSQL 16 + Redis) using Docker Compose:

1. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your production credentials
   ```

2. **Build and Launch Containers**
   ```bash
   docker-compose up -d --build
   ```

3. **Check Container Status**
   ```bash
   docker-compose ps
   ```

The application will be accessible at `http://localhost:8000` (or your configured domain).

---

## Demo Credentials (from Seeder)

| Role | Email | Password | Plan / Details |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@zacma.com` | `password` | Super Admin with full moderation access |
| **Standard User** | `john@example.com` | `password` | Basic Plan (20 listings), active Addis Ababa vehicle & apartment listings |
| **Standard User** | `abebe@example.com` | `password` | Premium Plan (50 listings), active vehicle listings |
| **Standard User** | `chaltu@example.com` | `password` | Basic Plan, real estate listings in Adama |

---

## API Architecture (`/api/v1/...`)

All endpoints are available under both `/api/v1/...` and `/api/...` for maximum compatibility.

### Authentication & Profile
- `POST /api/v1/auth/register` — Register a new universal account
- `POST /api/v1/auth/login` — Login via email or phone number
- `POST /api/v1/auth/logout` — Revoke token
- `GET  /api/v1/me` — Authenticated user profile, quota usage, active plan
- `POST /api/v1/profile` — Update user profile, photo, business name, dealer license
- `GET  /api/v1/profile/{username}` — Public profile & published listings

### Listings & Marketplace
- `GET  /api/v1/marketplace/listings` — Search published listings (filters: type, city, price, year, bedrooms, attributes)
- `GET  /api/v1/marketplace/listings/{slug}` — Single listing details
- `GET  /api/v1/marketplace/categories` — Categories by industry
- `GET  /api/v1/marketplace/cities` — Ethiopian cities index
- `GET  /api/v1/my-listings` — Authenticated user's listings
- `POST /api/v1/listings` — Create listing (enforces quota limit)
- `GET|PUT|DELETE /api/v1/listings/{id}` — Manage listing

### CRM & Inquiries
- `POST  /api/v1/inquiries` — Submit "Contact Seller" inquiry (auto-creates CRM lead)
- `GET   /api/v1/crm/leads` — Seller CRM lead inbox
- `PATCH /api/v1/crm/leads/{id}/status` — Update stage (`New`, `Contacted`, `Closed`)
- `GET|POST /api/v1/buyer-requirements` — Buyer requirements board

### Subscriptions & Payments
- `GET  /api/v1/plans` — Active plans with Monthly, Quarterly, Yearly pricing
- `POST /api/v1/subscriptions/checkout` — Initialize checkout with Chapa, Telebirr, CBE, eBirr, or SantimPay
- `GET  /api/v1/payments/history` — Payment transaction history
- `POST /api/v1/webhooks/payment/{provider}` — Idempotent webhook listener

### Reviews & Messaging
- `GET|POST /api/v1/listings/{id}/reviews` — Listing reviews & ratings
- `GET  /api/v1/messages/conversations` — In-app message inbox
- `GET  /api/v1/messages/thread/{user}` — Chat thread with seller/buyer
- `POST /api/v1/messages/send` — Send direct message

---

## Running Automated Tests

Run the full automated test suite covering authentication, policies, listing lifecycle, quota enforcement, CRM leads, payment webhooks, and V1 API endpoints:

```bash
php artisan test
```

To run only the MVP dealership & marketplace feature tests:
```bash
php artisan test --filter=Mvp
```

---

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
