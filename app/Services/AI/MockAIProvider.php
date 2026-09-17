<?php

namespace App\Services\AI;

class MockAIProvider implements AIProviderInterface
{
    public function getProviderName(): string
    {
        return 'mock';
    }

    public function generateText(string $prompt, array $options = []): array
    {
        $promptLower = strtolower($prompt);
        $text = '';

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
                    'category_slug' => 'electronics',
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
                    'category_slug' => 'vehicles',
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
        } elseif (str_contains($promptLower, 'subscription') || str_contains($promptLower, 'plan') || str_contains($promptLower, 'pricing') || str_contains($promptLower, 'quota')) {
            $text = "### Zacma Dealer Subscription Packages\n\n" .
                    "The platform offers 3 tailored monthly subscription tiers for dealerships:\n\n" .
                    "1. **Dealer Basic (5,000 ETB / month)**:\n" .
                    "   - Up to 5 listing posts per day\n" .
                    "   - Masked customer phone numbers (inquiries only to protect leads)\n" .
                    "   - Standard AI lead scoring & descriptions\n" .
                    "   - Up to 2 staff accounts\n\n" .
                    "2. **Dealer Premium (8,000 ETB / month)**:\n" .
                    "   - Up to 20 listing posts per day\n" .
                    "   - Full direct customer phone & WhatsApp click-to-chat access\n" .
                    "   - Storefront AI Customer Concierge chatbot\n" .
                    "   - Gemini Multimodal Vision photo detection auto-fill\n" .
                    "   - Up to 5 staff accounts\n\n" .
                    "3. **Dealer Advance (12,000 ETB / month)**:\n" .
                    "   - **Unlimited** listing posts per day\n" .
                    "   - Direct CSV contact and lead export\n" .
                    "   - **Dedicated Custom AI Concierge branded with your username (@username)**\n" .
                    "   - 24/7 automated sales lead qualification bot\n" .
                    "   - Unlimited team and sales agent seats\n" .
                    "   - Top priority marketplace placement\n\n" .
                    "You can upgrade your plan anytime at `/dealer/subscription` or directly via `/checkout/subscription/{plan_id}`.";
        } elseif (str_contains($promptLower, 'payment') || str_contains($promptLower, 'telebirr') || str_contains($promptLower, 'crypto') || str_contains($promptLower, 'paypal') || str_contains($promptLower, 'chapa') || str_contains($promptLower, 'santimpay')) {
            $text = "### Integrated Payment Gateways in Zacma\n\n" .
                    "The platform supports both local Ethiopian mobile banking and global international payment rails:\n\n" .
                    "- **Telebirr SuperApp**: Mobile money in ETB with instant server-to-server callback verification.\n" .
                    "- **SantimPay Mobile**: Ethiopian QR payments and mobile banking direct transfer.\n" .
                    "- **Chapa Pay**: Integrated banking checkout supporting CBE Birr, Awash Bank, Dashen Bank, and Amole.\n" .
                    "- **PayPal Express**: Global payments via PayPal accounts and international cards in USD.\n" .
                    "- **Stripe (Mastercard & Visa)**: Secure 256-bit encrypted card processing.\n" .
                    "- **Crypto (USDT / BTC / ETH)**: Web3 & crypto payments supporting USDT (TRC-20), Bitcoin, and Ethereum with instant transaction verification.\n" .
                    "- **Cash / Sandbox**: Showroom point-of-sale settlement or test simulation.\n\n" .
                    "All verified payments automatically generate digital VAT invoices and update order statuses.";
        } elseif (str_contains($promptLower, 'ai') && (str_contains($promptLower, 'vision') || str_contains($promptLower, 'listing') || str_contains($promptLower, 'create') || str_contains($promptLower, 'detect'))) {
            $text = "### Gemini Multimodal AI Listing Creation\n\n" .
                    "When adding a new listing at `/dealer/listings/create`, you can upload a product photo (vehicle, house, laptop, machinery, or watch). The AI will:\n\n" .
                    "1. **Identify the Item**: Automatically extract model, brand, year, and condition.\n" .
                    "2. **Category Mapping**: Match the item to its specific category (e.g. Vehicles, Real Estate, Electronics).\n" .
                    "3. **Fair Market Pricing**: Estimate realistic market value in ETB.\n" .
                    "4. **Sales Pitch**: Generate a professional 3-paragraph marketing description.\n" .
                    "5. **Dynamic Field Auto-Fill**: Populate category-specific custom attributes (mileage, transmission, bedrooms, RAM, etc.).";
        } elseif (str_contains($promptLower, 'category') || str_contains($promptLower, 'categories') || str_contains($promptLower, 'system') || str_contains($promptLower, 'project') || str_contains($promptLower, 'platform') || str_contains($promptLower, 'what is zacma')) {
            $text = "### About Zacma AI Platform\n\n" .
                    "**Zacma AI Platform** is an enterprise multi-tenant Marketplace + CRM SaaS engineered with Laravel 12, Eloquent ORM, Alpine.js, and Tailwind CSS.\n\n" .
                    "- **Universal Categories**: Not limited to cars! Fully supports Vehicles, Real Estate, Electronics, Furniture, Machinery, Agricultural Equipment, Jobs, Services, and Custom Categories.\n" .
                    "- **Multi-Tenancy**: Built with strict `TenantScope` isolation. Dealership data is strictly private and isolated.\n" .
                    "- **Integrated CRM**: Complete Customer 360 view, visual Kanban sales pipeline, algorithmic lead scoring (Hot, Warm, Cold), and calendar scheduling.\n" .
                    "- **Portals**: Dedicated spaces for Super Admin (`/super-admin`), Dealership Staff (`/dealer`), Buyers (`/customer`), and Profile Settings (`/profile`).\n" .
                    "- **AI Ecosystem**: Powered by Google Gemini for Multimodal Vision auto-detection, lead scoring, and role-aware assistant chat.";
        } elseif (str_contains($promptLower, 'profile') || str_contains($promptLower, 'avatar') || str_contains($promptLower, 'password')) {
            $text = "### User Profile & Security Settings\n\n" .
                    "You can manage your account anytime at `/profile`:\n\n" .
                    "- **Profile Picture**: Upload or change your custom photo (JPG, PNG, WebP up to 5MB) with instant client-side preview, or revert to default initials.\n" .
                    "- **Contact Information**: Update your Full Name, Email, and Phone number.\n" .
                    "- **Security**: Change your account password with current password verification.\n" .
                    "- **Account Overview**: Check your active role, dealership affiliation, and last login timestamp.";
        } elseif (str_contains($promptLower, 'crm') || str_contains($promptLower, 'pipeline') || str_contains($promptLower, 'kanban')) {
            $text = "### Zacma CRM Suite\n\n" .
                    "- **Visual Kanban Pipeline** (`/dealer/crm/pipeline`): Drag and drop deals across stages (New Lead, Contacted, Qualified, Proposal/Viewing, Negotiation, Closed Won, Closed Lost).\n" .
                    "- **Customer 360** (`/dealer/crm/contacts/{id}`): Deep view of contact timeline, inquiry history, notes, tasks, and orders.\n" .
                    "- **Automated Lead Scoring**: Ranks leads as HOT (red badge), WARM (yellow badge), or COLD (slate badge) based on interaction recency and activity.\n" .
                    "- **Follow-up Tasks & Calendar**: Prioritized task list (Overdue, Today, Upcoming) and scheduled test drive / viewing appointments.";
        } elseif (str_contains($promptLower, 'role: super_admin') || str_contains($promptLower, 'super admin')) {
            $text = "Zacma SaaS Director AI: System is operating normally across all organizations. We currently monitor multi-tenant activity, active subscription tiers, and marketplace payment volumes. You can configure dynamic categories, adjust subscription quotas, or audit security logs at any time from your control panel.";
        } elseif (str_contains($promptLower, 'role: customer') || str_contains($promptLower, 'buyer')) {
            $text = "Welcome to Zacma Concierge! I'm here to assist your shopping experience. You can search certified vehicles, luxury real estate, and high-grade electronics. If you find an item you like, you can directly book an inspection appointment or place a secure reservation deposit via Telebirr, SantimPay, or Chapa.";
        } elseif (str_contains($promptLower, 'hot lead') || str_contains($promptLower, 'score')) {
            $text = "Based on our interaction analysis: Customer exhibits high purchase intent with 4 recent vehicle inquiries, requested an in-person viewing, and responded within 2 hours. Recommended Action: Schedule immediate call and send quotation.";
        } elseif (str_contains($promptLower, 'summary') || str_contains($promptLower, 'summarize')) {
            $text = "Customer Summary: The client has active interest in listings, has viewed 6 properties/vehicles, scheduled 1 appointment, and has an ongoing negotiation deal. No follow-up in the last 48 hours.";
        } elseif (str_contains($promptLower, 'sms') || str_contains($promptLower, 'draft sms')) {
            $text = "Hello! Thank you for your interest in our listings at Zacma. We have prepared the details you requested. Would tomorrow 10:00 AM work for a quick viewing/call? Best regards, Sales Team.";
        } elseif (str_contains($promptLower, 'email') || str_contains($promptLower, 'draft email')) {
            $text = "Dear Customer,\n\nThank you for reaching out to us. We have updated our latest pricing and options tailored to your preferences. Please let us know when you would like to arrange an appointment or review financing options.\n\nWarm regards,\nZacma Dealership Team";
        } elseif (str_contains($promptLower, 'listing') || str_contains($promptLower, 'description')) {
            $text = "Exceptional opportunity! Pristine condition, meticulously maintained with full service history. Features modern specifications, premium comfort, and superior performance. Schedule your inspection today.";
        } else {
            $text = "Zacma AI Assistant: Ready to assist with your CRM, inventory management, customer inquiries, and sales pipeline operations. How can I help optimize your workflow today?";
        }

        return [
            'success' => true,
            'text' => $text,
            'tokens' => (int)(strlen($prompt . $text) / 4),
            'raw' => ['mode' => 'intelligent_mock'],
        ];
    }
}
