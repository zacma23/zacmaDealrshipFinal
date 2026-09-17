<?php

namespace App\Services\AI\Knowledge;

class PlatformKnowledge
{
    public static function getKnowledge(): string
    {
        return <<<TEXT
### 1. PLATFORM OVERVIEW & ARCHITECTURE
- **Platform Name**: Zacma Dealership & Multi-Industry Marketplace + CRM SaaS.
- **Primary Market**: Ethiopia-first with domestic currency support in Ethiopian Birr (ETB).
- **Core Mission**: Provide individuals, private sellers, and commercial dealerships across Ethiopia with a unified, high-performance marketplace, CRM lead pipeline, direct in-app messaging, and automated subscription monetization.
- **Multi-Industry Scope**: Not limited to automotive. The system powers four primary industry categories:
  1. **Vehicles** (Sedans, SUVs, 4x4s, Pickups, Commercial Trucks, Electric/Hybrid, Motorcycles).
  2. **Real Estate** (Residential Villas, Family Houses, Land/Plots, Commercial Buildings, Offices, Retail Shops, Warehouses).
  3. **Apartments** (Furnished/Unfurnished Condominiums, Serviced Apartments, Multi-family units for sale or monthly rent).
  4. **General Marketplace Products** (Automotive Spare Parts, Electronics, Smartphones, Laptops, Heavy Machinery, Furniture, Home Appliances).
- **Database Architecture**: Single polymorphic `listings` table utilizing dynamic JSONB `listing_attributes` for zero-overhead schema extensibility without database migrations.
- **Technology Stack**: Laravel 12 backend, PostgreSQL 16 (production) / SQLite (development), Redis caching, React 18 with TypeScript, Tailwind CSS, and Vite.
TEXT;
    }
}

