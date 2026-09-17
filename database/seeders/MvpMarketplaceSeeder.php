<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\Profile;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MvpMarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $superAdminRole = Role::firstOrCreate(['name' => Role::SUPER_ADMIN], [
            'display_name' => 'Super Administrator',
            'description' => 'Full platform access and listings moderation',
        ]);

        $userRole = Role::firstOrCreate(['name' => Role::USER], [
            'display_name' => 'Standard User',
            'description' => 'Marketplace buyer and seller account',
        ]);

        // 2. Fixed Subscription Plans (3 plans with Monthly, Quarterly, Yearly billing)
        $basicPlan = SubscriptionPlan::updateOrCreate(['slug' => 'basic'], [
            'name' => 'Basic',
            'price' => 0.00,
            'price_quarterly' => 0.00,
            'price_yearly' => 0.00,
            'currency' => 'ETB',
            'listing_limit' => 20,
            'billing_period' => 'monthly',
            'features' => [
                'Up to 20 active listings',
                'Standard search placement',
                'Basic CRM leads inbox',
                'Email notifications',
            ],
            'is_active' => true,
        ]);

        $premiumPlan = SubscriptionPlan::updateOrCreate(['slug' => 'premium'], [
            'name' => 'Premium',
            'price' => 499.00,
            'price_quarterly' => 1299.00, // discount
            'price_yearly' => 4499.00, // 2+ months free
            'currency' => 'ETB',
            'listing_limit' => 50,
            'billing_period' => 'monthly',
            'features' => [
                'Up to 50 active listings',
                'Priority marketplace search',
                'Inbound CRM lead management',
                'Fast-track listing approval',
                'Verified seller badge',
            ],
            'is_active' => true,
        ]);

        $proPlan = SubscriptionPlan::updateOrCreate(['slug' => 'pro'], [
            'name' => 'Pro Enterprise',
            'price' => 1499.00,
            'price_quarterly' => 3999.00,
            'price_yearly' => 13499.00,
            'currency' => 'ETB',
            'listing_limit' => 100,
            'billing_period' => 'monthly',
            'features' => [
                'Up to 100 active listings',
                'Featured badge on homepage',
                'Full CRM lead lifecycle',
                'Instant approval priority',
                'Dedicated support',
            ],
            'is_active' => true,
        ]);

        // 3. Super Admin User
        $admin = User::firstOrCreate(['email' => 'admin@zacma.com'], [
            'name' => 'Super Administrator',
            'username' => 'superadmin',
            'phone' => '+251911000001',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        // 4. Demo Users (One Account = Buyer + Seller)
        $john = User::firstOrCreate(['email' => 'john@example.com'], [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'phone' => '+251911223344',
            'password' => Hash::make('password'),
            'role' => User::ROLE_USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $john->roles()->syncWithoutDetaching([$userRole->id]);

        $abebe = User::firstOrCreate(['email' => 'abebe@example.com'], [
            'name' => 'Abebe Bikila',
            'username' => 'abebe-bikila',
            'phone' => '+251922334455',
            'password' => Hash::make('password'),
            'role' => User::ROLE_USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $abebe->roles()->syncWithoutDetaching([$userRole->id]);

        $chaltu = User::firstOrCreate(['email' => 'chaltu@example.com'], [
            'name' => 'Chaltu Tadesse',
            'username' => 'chaltu-t',
            'phone' => '+251933445566',
            'password' => Hash::make('password'),
            'role' => User::ROLE_USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $chaltu->roles()->syncWithoutDetaching([$userRole->id]);

        // Activate Premium subscription for Abebe
        Subscription::firstOrCreate(['user_id' => $abebe->id, 'plan_id' => $premiumPlan->id], [
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);

        // 5. Categories (Flat list, 3 types: vehicle, real_estate, apartment)
        $catVehicleSedan = Category::firstOrCreate(['slug' => 'sedan-cars'], [
            'name' => 'Sedan Cars',
            'type' => 'vehicle',
            'icon' => 'Car',
            'is_active' => true,
        ]);
        $catVehicleSuv = Category::firstOrCreate(['slug' => 'suv-crossover'], [
            'name' => 'SUV & 4x4',
            'type' => 'vehicle',
            'icon' => 'Truck',
            'is_active' => true,
        ]);
        $catRealEstateVilla = Category::firstOrCreate(['slug' => 'residential-villa'], [
            'name' => 'Residential Villas',
            'type' => 'real_estate',
            'icon' => 'Home',
            'is_active' => true,
        ]);
        $catRealEstateCommercial = Category::firstOrCreate(['slug' => 'commercial-buildings'], [
            'name' => 'Commercial Properties',
            'type' => 'real_estate',
            'icon' => 'Building',
            'is_active' => true,
        ]);
        $catApartmentFurnished = Category::firstOrCreate(['slug' => 'furnished-apartments'], [
            'name' => 'Furnished Apartments',
            'type' => 'apartment',
            'icon' => 'Bed',
            'is_active' => true,
        ]);
        $catApartmentStudio = Category::firstOrCreate(['slug' => 'studio-apartments'], [
            'name' => 'Studio & 1-Bed Apartments',
            'type' => 'apartment',
            'icon' => 'DoorOpen',
            'is_active' => true,
        ]);

        // 6. Realistic Ethiopia Seed Listings
        // A. Vehicle: Toyota Corolla (John Doe)
        $listing1 = Listing::firstOrCreate(['slug' => 'toyota-corolla-executive-2021-addis'], [
            'user_id' => $john->id,
            'category_id' => $catVehicleSedan->id,
            'type' => Listing::TYPE_VEHICLE,
            'title' => 'Toyota Corolla Executive 2021 (Automatic)',
            'description' => 'Immaculate condition Toyota Corolla, bought brand new and driven only 38,000 km in Addis Ababa. Full service history at Moenco. Fuel efficient, smooth transmission with rear camera.',
            'price' => 3850000.00,
            'currency' => 'ETB',
            'city' => 'Addis Ababa',
            'address' => 'Bole Medhanialem, Addis Ababa',
            'status' => Listing::STATUS_PUBLISHED,
            'year' => 2021,
            'listing_attributes' => [
                'brand' => 'Toyota',
                'model' => 'Corolla',
                'mileage' => 38000,
                'transmission' => 'Automatic',
                'fuel_type' => 'Petrol',
                'condition' => 'Clean Local Used',
            ],
            'published_at' => now()->subDays(2),
            'featured' => true,
            'views_count' => 142,
        ]);
        ListingImage::firstOrCreate(['listing_id' => $listing1->id, 'is_primary' => true], [
            'image_path' => 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=800&auto=format&fit=crop&q=70',
            'order' => 1,
        ]);

        // B. Vehicle: Hyundai Tucson 2023 (Abebe Bikila)
        $listing2 = Listing::firstOrCreate(['slug' => 'hyundai-tucson-hybrid-2023-hawassa'], [
            'user_id' => $abebe->id,
            'category_id' => $catVehicleSuv->id,
            'type' => Listing::TYPE_VEHICLE,
            'title' => 'Hyundai Tucson Smart Hybrid 2023',
            'description' => 'Modern 2023 Tucson with panoramic sunroof, leather seats, digital cluster. Exceptional fuel economy with hybrid electric motor. Zero accident history.',
            'price' => 5900000.00,
            'currency' => 'ETB',
            'city' => 'Hawassa',
            'address' => 'Piazza Area, Hawassa',
            'status' => Listing::STATUS_PUBLISHED,
            'year' => 2023,
            'listing_attributes' => [
                'brand' => 'Hyundai',
                'model' => 'Tucson',
                'mileage' => 18500,
                'transmission' => 'Automatic',
                'fuel_type' => 'Hybrid',
                'condition' => 'Excellent',
            ],
            'published_at' => now()->subDay(),
            'featured' => true,
            'views_count' => 98,
        ]);
        ListingImage::firstOrCreate(['listing_id' => $listing2->id, 'is_primary' => true], [
            'image_path' => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=800&auto=format&fit=crop&q=70',
            'order' => 1,
        ]);

        // C. Real Estate: Luxury Villa in CMC Addis (Chaltu Tadesse)
        $listing3 = Listing::firstOrCreate(['slug' => 'luxury-villa-4-bedrooms-cmc-addis'], [
            'user_id' => $chaltu->id,
            'category_id' => $catRealEstateVilla->id,
            'type' => Listing::TYPE_REAL_ESTATE,
            'title' => 'Modern 4-Bedroom Villa with Garden in CMC',
            'description' => 'Spacious 350 sqm gated compound villa in serene CMC neighbourhood. Features 4 master bedrooms, modern kitchen, maid room, parking for 3 cars, and continuous backup water tank.',
            'price' => 28000000.00,
            'currency' => 'ETB',
            'city' => 'Addis Ababa',
            'address' => 'CMC Near St. Michael, Addis Ababa',
            'status' => Listing::STATUS_PUBLISHED,
            'bedrooms' => 4,
            'listing_attributes' => [
                'bathrooms' => 4,
                'area_sqm' => 350,
                'property_purpose' => 'sale',
                'parking_spaces' => 3,
                'has_garden' => true,
            ],
            'published_at' => now()->subHours(12),
            'featured' => true,
            'views_count' => 260,
        ]);
        ListingImage::firstOrCreate(['listing_id' => $listing3->id, 'is_primary' => true], [
            'image_path' => 'https://images.unsplash.com/photo-1613977257363-707ba9348227?w=800&auto=format&fit=crop&q=70',
            'order' => 1,
        ]);

        // D. Apartment: 2-Bedroom Furnished in Bole (John Doe)
        $listing4 = Listing::firstOrCreate(['slug' => 'furnished-2bed-apartment-bole-atlas'], [
            'user_id' => $john->id,
            'category_id' => $catApartmentFurnished->id,
            'type' => Listing::TYPE_APARTMENT,
            'title' => 'Fully Furnished 2-Bedroom Apartment in Bole Atlas',
            'description' => 'Bright and modern apartment on the 6th floor with elevator, standby generator, security, high-speed Wi-Fi, and balcony city views. Walking distance to international restaurants.',
            'price' => 75000.00,
            'currency' => 'ETB',
            'city' => 'Addis Ababa',
            'address' => 'Bole Atlas, Addis Ababa',
            'status' => Listing::STATUS_PUBLISHED,
            'bedrooms' => 2,
            'listing_attributes' => [
                'bathrooms' => 2,
                'floor' => 6,
                'furnished' => 'yes',
                'rent_period' => 'monthly',
                'elevator' => true,
                'generator' => true,
            ],
            'published_at' => now()->subHours(6),
            'featured' => false,
            'views_count' => 84,
        ]);
        ListingImage::firstOrCreate(['listing_id' => $listing4->id, 'is_primary' => true], [
            'image_path' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800&auto=format&fit=crop&q=70',
            'order' => 1,
        ]);

        // E. Pending Listing for Admin Approval Queue testing
        Listing::firstOrCreate(['slug' => 'suzuki-dzire-2022-adama-pending'], [
            'user_id' => $abebe->id,
            'category_id' => $catVehicleSedan->id,
            'type' => Listing::TYPE_VEHICLE,
            'title' => 'Suzuki Dzire 2022 - Low Mileage',
            'description' => 'Single owner, pristine silver Suzuki Dzire. AC, power windows, Bluetooth stereo. Well kept in Adama.',
            'price' => 2450000.00,
            'currency' => 'ETB',
            'city' => 'Adama',
            'address' => 'Posta Bet, Adama',
            'status' => Listing::STATUS_PENDING,
            'year' => 2022,
            'listing_attributes' => [
                'brand' => 'Suzuki',
                'model' => 'Dzire',
                'mileage' => 24000,
                'transmission' => 'Manual',
                'fuel_type' => 'Petrol',
            ],
        ]);

        // F. Product: Spare Parts & Equipment (John Doe)
        $catProduct = Category::firstOrCreate(['slug' => 'auto-parts-equipment'], [
            'name' => 'Auto Parts & Equipment',
            'type' => 'product',
            'icon' => 'Wrench',
            'is_active' => true,
        ]);

        $listingProduct = Listing::firstOrCreate(['slug' => 'toyota-genuine-brake-pads-set'], [
            'user_id' => $john->id,
            'category_id' => $catProduct->id,
            'type' => Listing::TYPE_PRODUCT,
            'title' => 'Toyota Genuine Ceramic Brake Pads (Front & Rear Set)',
            'description' => 'Brand new original Toyota OEM ceramic brake pad set, imported from Dubai. Suitable for RAV4, Corolla, and Hilux 2018-2024 models.',
            'price' => 18500.00,
            'currency' => 'ETB',
            'city' => 'Addis Ababa',
            'address' => 'Teklehaymanot Auto Market, Addis Ababa',
            'status' => Listing::STATUS_PUBLISHED,
            'listing_attributes' => [
                'brand' => 'Toyota OEM',
                'condition' => 'Brand New',
                'warranty' => '6 Months',
                'part_number' => '04465-AZ200',
            ],
            'published_at' => now()->subDay(),
            'featured' => false,
            'views_count' => 65,
        ]);
        ListingImage::firstOrCreate(['listing_id' => $listingProduct->id, 'is_primary' => true], [
            'image_path' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&auto=format&fit=crop&q=70',
            'order' => 1,
        ]);

        // 7. Payment Gateways
        \App\Models\PaymentGateway::updateOrCreate(['code' => 'chapa'], [
            'name' => 'Chapa (Cards, Telebirr, CBEBirr)',
            'is_active' => true,
            'is_test_mode' => true,
        ]);
        \App\Models\PaymentGateway::updateOrCreate(['code' => 'telebirr'], [
            'name' => 'Telebirr Direct (Ethio Telecom)',
            'is_active' => true,
            'is_test_mode' => true,
        ]);
        \App\Models\PaymentGateway::updateOrCreate(['code' => 'cbe'], [
            'name' => 'Commercial Bank of Ethiopia (CBE Birr)',
            'is_active' => true,
            'is_test_mode' => true,
        ]);
        \App\Models\PaymentGateway::updateOrCreate(['code' => 'ebirr'], [
            'name' => 'eBirr Mobile Payment',
            'is_active' => true,
            'is_test_mode' => true,
        ]);
        \App\Models\PaymentGateway::updateOrCreate(['code' => 'santimpay'], [
            'name' => 'SantimPay Multi-Bank Gateway',
            'is_active' => true,
            'is_test_mode' => true,
        ]);

        // 8. Sample Reviews
        \App\Models\Review::firstOrCreate([
            'user_id' => $abebe->id,
            'listing_id' => $listing1->id,
        ], [
            'seller_id' => $john->id,
            'rating' => 5,
            'comment' => 'Excellent seller! The car was exactly as described, smooth inspection process in Bole.',
            'is_approved' => true,
        ]);

        // 9. Sample Buyer Requirement (CRM)
        \App\Models\BuyerRequirement::firstOrCreate([
            'title' => 'Seeking 2019-2022 Toyota RAV4 AWD under 5M ETB',
        ], [
            'user_id' => $chaltu->id,
            'type' => 'vehicle',
            'city' => 'Addis Ababa',
            'budget_min' => 4000000.00,
            'budget_max' => 5000000.00,
            'currency' => 'ETB',
            'description' => 'Looking for clean, low-mileage Toyota RAV4 (petrol or hybrid). Serious cash buyer, ready for immediate transfer.',
            'specifications' => [
                'model' => 'RAV4',
                'transmission' => 'Automatic',
                'fuel' => 'Petrol or Hybrid',
            ],
            'status' => 'open',
        ]);
    }
}

