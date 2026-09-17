<?php

namespace App\Services\AI;

use App\Services\AI\Knowledge\CrmAndBuyerRequirementsKnowledge;
use App\Services\AI\Knowledge\FaqAndTroubleshootingKnowledge;
use App\Services\AI\Knowledge\IndustrySpecificationsKnowledge;
use App\Services\AI\Knowledge\ListingLifecycleKnowledge;
use App\Services\AI\Knowledge\PlatformKnowledge;
use App\Services\AI\Knowledge\RolesAndPermissionsKnowledge;
use App\Services\AI\Knowledge\SubscriptionsAndPaymentsKnowledge;

class MockAIProvider implements AIProviderInterface
{
    public function getProviderName(): string
    {
        return 'knowledge_engine';
    }

    public function generateText(string $prompt, array $options = []): array
    {
        $promptLower = strtolower($prompt);
        $text = '';

        // Check for Vision/JSON detection request
        if (!empty($options['json_mode']) || str_contains($promptLower, 'detect listing') || str_contains($promptLower, 'analyze listing')) {
            if (str_contains($promptLower, 'house') || str_contains($promptLower, 'villa') || str_contains($promptLower, 'apartment') || str_contains($promptLower, 'property')) {
                $payload = [
                    'title' => 'Modern 4-Bedroom Luxury Villa in Bole',
                    'category_slug' => 'real-estate',
                    'suggested_price' => 28500000,
                    'currency' => 'ETB',
                    'price_type' => 'negotiable',
                    'city' => 'Addis Ababa',
                    'address' => 'Bole Sub-city, Near Rwanda Embassy',
                    'description' => 'Architecturally designed modern 4-bedroom luxury villa. Features spacious en-suite master bedroom, contemporary open-concept kitchen, landscaped garden, backup generator, water reservoir, and 24/7 security. Ready for immediate occupancy.',
                    'fields' => [
                        'Property Type' => 'Villa',
                        'Bedrooms' => '4',
                        'Bathrooms' => '4.5',
                        'Area (sqm)' => '450',
                        'Furnished' => 'Semi-Furnished',
                    ],
                    'features' => ['Backup Generator', 'Water Tank', 'Parking for 4 Cars', 'Security Gate', 'Modern Kitchen']
                ];
            } elseif (str_contains($promptLower, 'phone') || str_contains($promptLower, 'laptop') || str_contains($promptLower, 'computer') || str_contains($promptLower, 'electronics')) {
                $payload = [
                    'title' => 'Apple MacBook Pro 16" M3 Pro 36GB / 512GB Space Black',
                    'category_slug' => 'product',
                    'suggested_price' => 380000,
                    'currency' => 'ETB',
                    'price_type' => 'fixed',
                    'city' => 'Addis Ababa',
                    'address' => 'Bole Medhanialem Commercial Center',
                    'description' => 'Factory sealed Apple MacBook Pro 16-inch featuring the breakthrough M3 Pro chip. 36GB Unified Memory, 512GB ultra-fast SSD, Liquid Retina XDR display with ProMotion 120Hz. Complete with 1-year warranty and original accessories.',
                    'fields' => [
                        'Brand' => 'Apple',
                        'Model' => 'MacBook Pro 16" M3 Pro',
                        'RAM' => '36 GB',
                        'Storage' => '512 GB SSD',
                        'Condition' => 'Brand New Sealed',
                    ],
                    'features' => ['Liquid Retina XDR', 'MagSafe 3 Charging', 'Three Thunderbolt 4 Ports', '1 Year Warranty']
                ];
            } else {
                $payload = [
                    'title' => '2023 Toyota RAV4 XLE Hybrid AWD',
                    'category_slug' => 'vehicle',
                    'suggested_price' => 5400000,
                    'currency' => 'ETB',
                    'price_type' => 'fixed',
                    'city' => 'Addis Ababa',
                    'address' => 'CMC St. Michael, Dealership Showroom',
                    'description' => 'Immaculate 2023 Toyota RAV4 XLE Hybrid with Intelligent All-Wheel Drive. Delivers phenomenal 40 MPG fuel economy paired with responsive power. Includes Toyota Safety Sense 2.5, blind spot monitoring, push button start, and dual-zone climate control.',
                    'fields' => [
                        'Make' => 'Toyota',
                        'Model' => 'RAV4',
                        'Year' => '2023',
                        'Transmission' => 'Automatic',
                        'Fuel Type' => 'Hybrid',
                        'Mileage' => '19,200 km',
                        'Condition' => 'Foreign Used',
                    ],
                    'features' => ['All-Wheel Drive', 'Backup Camera', 'Apple CarPlay', 'Alloy Wheels', 'Blind Spot Monitor']
                ];
            }
            $text = json_encode($payload, JSON_PRETTY_PRINT);

            return [
                'success' => true,
                'text' => $text,
                'tokens' => (int)(strlen($prompt . $text) / 4),
                'raw' => ['mode' => 'vision_detection_mock'],
            ];
        }

        // Safety Guardrail: Check for destructive action requests
        if (SystemKnowledgeBase::isDestructiveOrMutatingRequest($promptLower)) {
            $text = SystemKnowledgeBase::getSafeRefusalMessage();
        } 
        // 1. One Account Rule & Roles
        elseif (str_contains($promptLower, 'separate') || str_contains($promptLower, 'seller account') || str_contains($promptLower, 'can i sell') || str_contains($promptLower, 'one account') || str_contains($promptLower, 'buyer and seller')) {
            $text = "### The Zacma Universal User Model: One Account = Buyer + Seller\n\n" .
                    "**Yes, absolutely!** You **never** need a separate vendor or seller account to sell items on Zacma.\n\n" .
                    "- **Single Account Power**: Every registered account can browse, favorite, inquire on listings, and publish their own listings.\n" .
                    "- **Instant Listing**: Simply click the green **'+ Add Listing'** button in the top navigation bar at any time to post a vehicle, property, apartment, or product.\n" .
                    "- **Inbound Leads**: When buyers inquire on your listings, their contacts and messages are automatically routed directly to your **'CRM Leads'** tab.\n" .
                    "- **Account Tiers**: In your profile settings, you can declare your profile as an **Individual**, **Business**, or **Dealership** (adding trade license and TIN numbers for verified status).";
        }
        // 2. Listing Lifecycle & Moderation (Check before general words)
        elseif (str_contains($promptLower, 'pending') || str_contains($promptLower, 'reject') || str_contains($promptLower, 'approval') || str_contains($promptLower, 'approve') || str_contains($promptLower, 'lifecycle') || str_contains($promptLower, 'moderation')) {
            $text = "### Listing Lifecycle & Moderation Workflow\n\n" .
                    "Every listing on Zacma progresses through a clear lifecycle to guarantee safety and prevent fraudulent posts:\n\n" .
                    "```text\n" .
                    "Create Listing -> Status: 'pending' -> Admin Review -> 'published' -> Buyer Inquiries -> CRM Leads -> Sold\n" .
                    "```\n\n" .
                    "- **Why is my listing pending?**: Newly created listings are reviewed by the moderation team to verify accurate specs and fair pricing in ETB. Review typically takes a few hours.\n" .
                    "- **What if my listing is rejected?**: When a listing is rejected, the Super Admin provides a specific `rejection_reason`. You can view this feedback in **'My Listings'**, click **'Edit'**, resolve the issue, and resubmit.\n" .
                    "- **Published Status**: Once approved, your listing becomes immediately visible in public search and category filters.";
        }
        // 3. Subscriptions & Listing Quotas
        elseif (preg_match('/\b(quota|quotas|subscription|subscriptions|pricing|limit|limits|upgrade|basic|premium|pro|plans?)\b/i', $promptLower)) {
            $text = "### Zacma Subscription Plans & Listing Quotas\n\n" .
                    "Zacma provides three tiered subscription plans designed for individual sellers and commercial dealerships:\n\n" .
                    "1. **Basic Plan (Free / 0 ETB)**:\n" .
                    "   - **Quota**: Up to **20 active listings**.\n" .
                    "   - **Features**: Standard marketplace search, inbound CRM leads inbox, direct in-app messaging.\n\n" .
                    "2. **Premium Plan (499 ETB / month)**:\n" .
                    "   - **Quarterly**: 1,299 ETB (10% discount).\n" .
                    "   - **Yearly**: 4,499 ETB (2+ months free).\n" .
                    "   - **Quota**: Up to **50 active listings**.\n" .
                    "   - **Features**: Priority search placement, verified badge eligibility, priority lead routing.\n\n" .
                    "3. **Pro Plan (1,499 ETB / month)**:\n" .
                    "   - **Quarterly**: 3,999 ETB (10% discount).\n" .
                    "   - **Yearly**: 13,499 ETB (2+ months free).\n" .
                    "   - **Quota**: Up to **100 active listings**.\n" .
                    "   - **Features**: Featured badge on homepage, instant moderation priority, complete CRM lead pipeline, dedicated account manager.\n\n" .
                    "**Hard Limit Enforcement**: If you reach your plan quota limit, new listing creation is safely held until you upgrade your plan under the **'Plans & Pricing'** tab.";
        }
        // 4. Payment Gateways (Ethiopia)
        elseif (str_contains($promptLower, 'payment') || str_contains($promptLower, 'chapa') || str_contains($promptLower, 'telebirr') || str_contains($promptLower, 'cbe') || str_contains($promptLower, 'ebirr') || str_contains($promptLower, 'santim') || str_contains($promptLower, 'gateway')) {
            $text = "### Supported Ethiopian Payment Gateways (ETB)\n\n" .
                    "Zacma natively integrates 5 trusted domestic Ethiopian payment options for instant digital subscription activation:\n\n" .
                    "1. **Chapa**: Seamless checkout supporting local Ethiopian debit cards, CBE, Awash, Dashen, and Amole.\n" .
                    "2. **Telebirr**: Direct mobile money payment through Ethio Telecom's SuperApp.\n" .
                    "3. **CBE Birr**: Commercial Bank of Ethiopia mobile money integration.\n" .
                    "4. **eBirr**: Convenient mobile wallet payment rail.\n" .
                    "5. **SantimPay**: Fast mobile banking and QR checkout in ETB.\n\n" .
                    "**Security**: All gateway transactions use digital HMAC webhook signatures and replay-proof idempotency checks to ensure payment integrity.";
        }
        // 5. CRM & Buyer Requirements
        elseif (str_contains($promptLower, 'crm') || str_contains($promptLower, 'lead') || str_contains($promptLower, 'pipeline') || str_contains($promptLower, 'buyer need') || str_contains($promptLower, 'requirement')) {
            $text = "### CRM Leads Pipeline & Buyer Requirements Hub\n\n" .
                    "Zacma bridges the gap between buyers and sellers with built-in CRM tools:\n\n" .
                    "1. **Inbound CRM Pipeline ('CRM Leads' Tab)**:\n" .
                    "   - Whenever a customer clicks 'Contact Seller', a new lead is instantly created in your pipeline.\n" .
                    "   - Move leads through 3 stages: **New** -> **Contacted** -> **Closed**.\n" .
                    "   - Schedule follow-up dates and record private internal notes.\n\n" .
                    "2. **Buyer Needs Board ('Buyer Needs' Tab)**:\n" .
                    "   - If a buyer cannot find what they want, they can post a public requirement with their target ETB budget.\n" .
                    "   - Verified dealers and sellers can browse these requests and click **'Connect'** to message the buyer directly with matching inventory.";
        }
        // 6. Vehicles
        elseif (str_contains($promptLower, 'car') || str_contains($promptLower, 'vehicle') || str_contains($promptLower, 'find a car') || str_contains($promptLower, 'toyota') || str_contains($promptLower, 'hyundai')) {
            $text = "### Searching and Listing Vehicles on Zacma\n\n" .
                    "- **How to Find Vehicles**: Open the **'Browse Market'** tab and select **'Vehicles'**. Filter by city (Addis Ababa, Hawassa, Adama, etc.), price range in ETB, year, fuel type (Petrol, Diesel, Hybrid, Electric), or transmission.\n" .
                    "- **Vehicle Specifications Supported**: Brand, Model, Year, Mileage (km), Transmission, Fuel Type, Body Type, Condition (Brand New, Foreign Used, Clean Local Used), and Color.\n" .
                    "- **How to Post a Car**: Click **'+ Add Listing'**, select **Vehicle**, fill in specs, upload photos, and click Submit for Approval.";
        }
        // 7. Real Estate & Apartments
        elseif (str_contains($promptLower, 'apartment') || str_contains($promptLower, 'real estate') || str_contains($promptLower, 'villa') || str_contains($promptLower, 'house') || str_contains($promptLower, 'property') || str_contains($promptLower, 'land')) {
            $text = "### Real Estate & Apartments on Zacma\n\n" .
                    "- **Real Estate**: Includes residential villas, family houses, commercial buildings, offices, and plots of land for sale or lease.\n" .
                    "  - *Attributes*: Bedrooms, Bathrooms, Area in square meters (sqm), Parking slots, Garden, Backup generator, Water reservoir.\n" .
                    "- **Apartments & Condominiums**: Dedicated focus on urban apartments in Addis Ababa and major cities.\n" .
                    "  - *Attributes*: Floor number, Furnished status (Furnished / Unfurnished), Rent period (Monthly rent in ETB), Elevator access, 24/7 Security, Standby generator.\n" .
                    "- **Searching**: Use the **'Real Estate'** and **'Apartments'** filter tabs in **Browse Market** to filter by bedrooms and city location.";
        }
        // 8. General Products & Parts
        elseif (str_contains($promptLower, 'product') || str_contains($promptLower, 'part') || str_contains($promptLower, 'electronics') || str_contains($promptLower, 'furniture') || str_contains($promptLower, 'machinery')) {
            $text = "### General Marketplace Products & Spare Parts\n\n" .
                    "Zacma features an extensible product catalog supporting:\n" .
                    "- **Automotive Spare Parts**: Brake pads, filters, engine components, body parts with OEM part numbers and warranty details.\n" .
                    "- **Electronics & Computers**: Smartphones, laptops, commercial equipment.\n" .
                    "- **Machinery & Furniture**: Heavy machinery, office equipment, home furnishings.\n\n" .
                    "Select the **'Products'** tab in Browse Market to discover or post items with custom brand and warranty specifications.";
        }
        // Default: Platform Overview & Quick Navigation
        else {
            $text = "### Welcome to Zacma AI Assistant!\n\n" .
                    "I am your knowledge copilot for the **Zacma Dealership & Marketplace SaaS**.\n\n" .
                    "Here are key things I can help you with:\n" .
                    "- **Selling on Zacma**: Learn how any user can post listings directly (**One Account = Buyer + Seller**).\n" .
                    "- **Subscriptions & Quotas**: Understand the Basic (20), Premium (50), and Pro (100) plans and limits.\n" .
                    "- **Ethiopian Payments**: Guidance on Chapa, Telebirr, CBE Birr, eBirr, and SantimPay.\n" .
                    "- **CRM & Inquiries**: How customer inquiries automatically become inbound seller leads.\n" .
                    "- **Buyer Needs Board**: How to post what you are looking for with target ETB budgets.\n\n" .
                    "Feel free to ask any specific question, or click one of the suggested prompts below!";
        }

        return [
            'success' => true,
            'text' => $text,
            'tokens' => (int)(strlen($prompt . $text) / 4),
            'raw' => ['mode' => 'knowledge_engine'],
        ];
    }
}
