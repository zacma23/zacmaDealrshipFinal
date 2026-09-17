<?php

namespace App\Services\AI\Knowledge;

class IndustrySpecificationsKnowledge
{
    public static function getKnowledge(): string
    {
        return <<<TEXT
### 4. MULTI-INDUSTRY SPECIFICATIONS & DATA ATTRIBUTES

#### A. Vehicles (Cars, SUVs, Trucks, Motorcycles)
- **Core Database Fields**: `year`, `price` (in ETB), `city`, `address`, `primary_image`, `images`.
- **Dynamic Attributes (`listing_attributes`)**:
  - `brand` (e.g. Toyota, Hyundai, Isuzu, Mercedes-Benz, BYD, Suzuki)
  - `model` (e.g. Corolla, RAV4, Tucson, Hilux, Atto 3)
  - `mileage` (in kilometers, e.g. 35,000 km)
  - `fuel_type` (Petrol, Diesel, Hybrid, Electric)
  - `transmission` (Automatic, Manual)
  - `condition` (Brand New, Foreign Used, Clean Local Used)
  - `body_type` (Sedan, SUV/4x4, Pickup, Hatchback, Van)
  - `color` (Exterior and interior colors)

#### B. Real Estate (Houses, Villas, Land, Commercial)
- **Core Database Fields**: `bedrooms`, `price` (in ETB), `city`, `address`, `primary_image`, `images`.
- **Dynamic Attributes (`listing_attributes`)**:
  - `property_purpose` (Sale or Rent)
  - `property_type` (Villa, Residential House, Land/Plot, Commercial Building, Office, Shop, Warehouse)
  - `bathrooms` (e.g. 2, 3, 4.5)
  - `area_sqm` (Total built-up or plot area in square meters, e.g. 350 sqm)
  - `parking_spaces` (Number of dedicated vehicle slots)
  - `has_garden` (Boolean true/false)
  - `backup_generator` (Boolean true/false)
  - `water_tank` (Continuous reservoir capacity)

#### C. Apartments & Condominiums
- **Core Database Fields**: `bedrooms`, `price` (in ETB - monthly rent or purchase price), `city`, `address`.
- **Dynamic Attributes (`listing_attributes`)**:
  - `apartment_name` or building complex
  - `floor` (e.g. 4th floor, Penthouse)
  - `bathrooms` (e.g. 1, 2)
  - `furnished` ('yes', 'no', 'semi')
  - `rent_period` ('monthly', 'yearly', or 'for_sale')
  - `elevator` (Elevator access in building)
  - `generator` (Standby generator for uninterrupted power)
  - `security` (24/7 building guards, CCTV)

#### D. General Marketplace Products
- **Categories**: Auto Spare Parts, Electronics, Smartphones, Computers, Furniture, Machinery, Home Appliances.
- **Dynamic Attributes (`listing_attributes`)**:
  - `brand` (e.g. Apple, Samsung, Bosch, CAT, Toyota OEM)
  - `condition` (Brand New, Refurbished, Used)
  - `part_number` (For OEM automotive or machinery parts)
  - `warranty` (e.g. 6 Months, 1 Year, No Warranty)
  - `model_compatibility` (Target vehicle or equipment compatibility)
TEXT;
    }
}
