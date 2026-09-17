<?php

namespace App\Services\AI\Knowledge;

class FaqAndTroubleshootingKnowledge
{
    public static function getKnowledge(): string
    {
        return <<<TEXT
### 7. FREQUENTLY ASKED QUESTIONS & STEP-BY-STEP GUIDES

**Q1: How do I find a car?**
- Navigate to the "Browse Market" tab.
- Click the "Vehicles" filter tab at the top.
- Filter by Ethiopian city (e.g. Addis Ababa, Hawassa, Adama), price range in ETB, year, fuel type (Petrol, Diesel, Hybrid, Electric), or transmission.
- Click on any vehicle card to view high-resolution photos, mileage, specifications, and seller details.

**Q2: How do I search for an apartment or villa?**
- In the "Browse Market" tab, select either "Real Estate" (for houses, villas, and commercial properties) or "Apartments" (for furnished/unfurnished flats).
- Filter by city, budget, number of bedrooms (1, 2, 3, 4+), and amenities such as backup generator or elevator.

**Q3: How do I contact a seller?**
- Open the listing by clicking on it.
- In the right-hand panel, you can:
  1. Fill out the "Contact Seller" form (Full name, phone, message). This creates an immediate lead in the seller's CRM pipeline.
  2. Click "Message Seller Directly" to open an in-app live chat thread.

**Q4: How do I save or favorite a listing?**
- Click the heart icon on any listing card or in the listing detail header.
- Your saved items are permanently stored under the "Saved" tab in the navigation bar.

**Q5: How do I create a buyer requirement?**
- Go to the "Buyer Needs" tab in the top navigation bar.
- Click "Post Buyer Request".
- Select the industry (Vehicle, Real Estate, Apartment, Product), enter your summary title, city, target ETB budget (Max/Min), and detailed specs.
- Dealers and sellers will review your requirement and message you directly.

**Q6: Can I sell without a separate seller account?**
- **YES! Absolutely.** Zacma uses a universal account architecture. Every registered user can browse, buy, and sell. Simply click the green "+ Add Listing" button in the top right corner.

**Q7: How do I create my own listing?**
1. Click "+ Add Listing" in the top navigation.
2. Select your category: Vehicle, Real Estate, Apartment, or Product.
3. Enter title, price in ETB, city, address, and category-specific specs (e.g. brand, model, year, bedrooms).
4. Provide image URLs (or upload photos) and description.
5. Click "Submit Listing for Approval".

**Q8: Why is my listing pending?**
- All new listings are reviewed by the moderation team to maintain high trust and verify accurate specs and fair ETB pricing. Approval typically takes just a few hours.

**Q9: Why was my listing rejected and how do I resubmit?**
- If a listing does not meet community guidelines (unclear photos, missing vehicle details, unrealistic pricing), it will be marked as "Rejected" with a specific reason.
- Go to "My Listings", locate the rejected item, read the admin feedback reason, click "Edit", fix the requested details, and save to resubmit.

**Q10: How does the listing quota work?**
- Basic plan includes 20 free active listings.
- Premium includes 50 active listings for 499 ETB/mo.
- Pro includes 100 active listings for 1,499 ETB/mo.
- If you reach your plan limit, upgrade in the "Plans & Pricing" tab to post more items.

**Q11: What payment methods are supported?**
- Domestic Ethiopian payment options in ETB: **Chapa**, **Telebirr**, **CBE Birr**, **eBirr**, and **SantimPay**.
TEXT;
    }
}

