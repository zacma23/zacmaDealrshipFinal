<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\ResellerApiKey;
use App\Models\SmmCategory;
use App\Models\SmmPlatform;
use App\Models\SmmProvider;
use App\Models\SmmService;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SmmMarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Providers
        $mockProvider = SmmProvider::updateOrCreate(
            ['name' => 'Zacma Sandbox SMM Provider'],
            [
                'api_url' => 'https://api.sandbox-smm.zacma.com/v2',
                'api_key' => 'mock_sec_key_' . Str::random(32),
                'adapter_type' => 'mock_sandbox',
                'status' => 'active',
                'balance' => 4850.5000,
                'currency' => 'USD',
                'priority' => 1,
                'health_status' => 'healthy',
            ]
        );

        $primaryProvider = SmmProvider::updateOrCreate(
            ['name' => 'Zacma Direct Global Gateway'],
            [
                'api_url' => 'https://api.smmglobalgateway.com/v2',
                'api_key' => 'live_sec_key_' . Str::random(32),
                'adapter_type' => 'standard_v2',
                'status' => 'active',
                'balance' => 12450.0000,
                'currency' => 'USD',
                'priority' => 2,
                'fallback_provider_id' => $mockProvider->id,
                'health_status' => 'healthy',
            ]
        );

        // 2. Platforms & Categories & Services
        $platformsData = [
            [
                'name' => 'Instagram',
                'slug' => 'instagram',
                'icon' => 'fa-brands fa-instagram',
                'sort_order' => 1,
                'categories' => [
                    [
                        'name' => 'Followers',
                        'slug' => 'instagram-followers',
                        'icon' => 'fa-solid fa-users',
                        'services' => [
                            [
                                'name' => 'Instagram Followers — High Quality [Instant | 30 Days Refill]',
                                'cost_per_k' => 0.85,
                                'customer_price_per_k' => 1.80,
                                'reseller_price_per_k' => 1.25,
                                'min_quantity' => 100,
                                'max_quantity' => 100000,
                                'has_refill' => true,
                                'refill_days' => 30,
                                'has_cancel' => true,
                                'start_time' => '0 - 15 Mins',
                                'completion_time' => '10K - 20K / Day',
                                'quality_level' => 'High Quality (Real Look)',
                                'geo_targeting' => 'Global Mixed',
                                'dripfeed_supported' => true,
                            ],
                            [
                                'name' => 'Instagram Followers — Real & Active Accounts [No Drop | Lifetime]',
                                'cost_per_k' => 1.95,
                                'customer_price_per_k' => 3.90,
                                'reseller_price_per_k' => 2.80,
                                'min_quantity' => 50,
                                'max_quantity' => 50000,
                                'has_refill' => true,
                                'refill_days' => 365,
                                'has_cancel' => false,
                                'start_time' => '30 Mins - 1 Hour',
                                'completion_time' => '5K / Day Gradual',
                                'quality_level' => 'Premium Ultra Real',
                                'geo_targeting' => 'USA & Europe',
                                'dripfeed_supported' => true,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Likes',
                        'slug' => 'instagram-likes',
                        'icon' => 'fa-solid fa-heart',
                        'services' => [
                            [
                                'name' => 'Instagram Likes — Instant Speed [Non-Drop | HQ]',
                                'cost_per_k' => 0.25,
                                'customer_price_per_k' => 0.65,
                                'reseller_price_per_k' => 0.42,
                                'min_quantity' => 50,
                                'max_quantity' => 200000,
                                'has_refill' => true,
                                'refill_days' => 30,
                                'has_cancel' => true,
                                'start_time' => 'Instant (0 - 5 Mins)',
                                'completion_time' => '50K / Day Fast',
                                'quality_level' => 'HQ Profiles',
                                'geo_targeting' => 'Global',
                                'dripfeed_supported' => true,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Reels & Video Views',
                        'slug' => 'instagram-views',
                        'icon' => 'fa-solid fa-play',
                        'services' => [
                            [
                                'name' => 'Instagram Reels Views — Viral Algorithm Push [Instant]',
                                'cost_per_k' => 0.08,
                                'customer_price_per_k' => 0.25,
                                'reseller_price_per_k' => 0.15,
                                'min_quantity' => 500,
                                'max_quantity' => 10000000,
                                'has_refill' => false,
                                'refill_days' => 0,
                                'has_cancel' => false,
                                'start_time' => 'Instant',
                                'completion_time' => '1M / Day High Speed',
                                'quality_level' => 'High Retention',
                                'geo_targeting' => 'Global',
                                'dripfeed_supported' => false,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'TikTok',
                'slug' => 'tiktok',
                'icon' => 'fa-brands fa-tiktok',
                'sort_order' => 2,
                'categories' => [
                    [
                        'name' => 'Followers',
                        'slug' => 'tiktok-followers',
                        'icon' => 'fa-solid fa-user-plus',
                        'services' => [
                            [
                                'name' => 'TikTok Followers — Stable Delivery [30 Days Refill Button]',
                                'cost_per_k' => 1.40,
                                'customer_price_per_k' => 2.90,
                                'reseller_price_per_k' => 2.10,
                                'min_quantity' => 100,
                                'max_quantity' => 100000,
                                'has_refill' => true,
                                'refill_days' => 30,
                                'has_cancel' => true,
                                'start_time' => '0 - 1 Hour',
                                'completion_time' => '10K / Day',
                                'quality_level' => 'Real Profiles with Bio',
                                'geo_targeting' => 'Global Mixed',
                                'dripfeed_supported' => true,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Likes',
                        'slug' => 'tiktok-likes',
                        'icon' => 'fa-solid fa-heart',
                        'services' => [
                            [
                                'name' => 'TikTok Likes — High Quality [Instant Start]',
                                'cost_per_k' => 0.45,
                                'customer_price_per_k' => 0.95,
                                'reseller_price_per_k' => 0.70,
                                'min_quantity' => 100,
                                'max_quantity' => 250000,
                                'has_refill' => true,
                                'refill_days' => 30,
                                'has_cancel' => true,
                                'start_time' => 'Instant',
                                'completion_time' => '30K / Day',
                                'quality_level' => 'High Quality',
                                'geo_targeting' => 'Global',
                                'dripfeed_supported' => true,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Views',
                        'slug' => 'tiktok-views',
                        'icon' => 'fa-solid fa-eye',
                        'services' => [
                            [
                                'name' => 'TikTok Video Views — Ultra Fast [Live Stream & Videos]',
                                'cost_per_k' => 0.04,
                                'customer_price_per_k' => 0.15,
                                'reseller_price_per_k' => 0.08,
                                'min_quantity' => 1000,
                                'max_quantity' => 50000000,
                                'has_refill' => false,
                                'refill_days' => 0,
                                'has_cancel' => false,
                                'start_time' => 'Instant',
                                'completion_time' => '5M / Day Turbo',
                                'quality_level' => 'Standard Real Look',
                                'geo_targeting' => 'Global',
                                'dripfeed_supported' => false,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'YouTube',
                'slug' => 'youtube',
                'icon' => 'fa-brands fa-youtube',
                'sort_order' => 3,
                'categories' => [
                    [
                        'name' => 'Subscribers',
                        'slug' => 'youtube-subscribers',
                        'icon' => 'fa-solid fa-bell',
                        'services' => [
                            [
                                'name' => 'YouTube Subscribers — Non-Drop [Monetization Safe | Lifetime Refill]',
                                'cost_per_k' => 8.50,
                                'customer_price_per_k' => 16.50,
                                'reseller_price_per_k' => 12.00,
                                'min_quantity' => 50,
                                'max_quantity' => 20000,
                                'has_refill' => true,
                                'refill_days' => 365,
                                'has_cancel' => false,
                                'start_time' => '1 - 6 Hours',
                                'completion_time' => '500 - 1K / Day Natural',
                                'quality_level' => 'Monetization Compliant Real Accounts',
                                'geo_targeting' => 'Global Mixed',
                                'dripfeed_supported' => false,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Watch Hours & Views',
                        'slug' => 'youtube-views',
                        'icon' => 'fa-solid fa-clock',
                        'services' => [
                            [
                                'name' => 'YouTube High Retention Views — Suggested Videos & Browse Features',
                                'cost_per_k' => 1.80,
                                'customer_price_per_k' => 3.50,
                                'reseller_price_per_k' => 2.60,
                                'min_quantity' => 500,
                                'max_quantity' => 2000000,
                                'has_refill' => true,
                                'refill_days' => 60,
                                'has_cancel' => true,
                                'start_time' => '0 - 2 Hours',
                                'completion_time' => '20K - 50K / Day',
                                'quality_level' => 'Ultra High Retention (3-5+ mins)',
                                'geo_targeting' => 'Global Organic Mix',
                                'dripfeed_supported' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Telegram',
                'slug' => 'telegram',
                'icon' => 'fa-brands fa-telegram',
                'sort_order' => 4,
                'categories' => [
                    [
                        'name' => 'Channel Members',
                        'slug' => 'telegram-members',
                        'icon' => 'fa-solid fa-users-line',
                        'services' => [
                            [
                                'name' => 'Telegram Channel/Group Members — Zero Drop [30 Days Refill]',
                                'cost_per_k' => 0.90,
                                'customer_price_per_k' => 1.95,
                                'reseller_price_per_k' => 1.35,
                                'min_quantity' => 100,
                                'max_quantity' => 150000,
                                'has_refill' => true,
                                'refill_days' => 30,
                                'has_cancel' => true,
                                'start_time' => 'Instant (0 - 15 Mins)',
                                'completion_time' => '30K / Day Clean Speed',
                                'quality_level' => 'High Quality Profiles with Names',
                                'geo_targeting' => 'Global Mixed',
                                'dripfeed_supported' => true,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Post Views',
                        'slug' => 'telegram-views',
                        'icon' => 'fa-solid fa-eye',
                        'services' => [
                            [
                                'name' => 'Telegram Post Views — Last 10 Posts Package [Auto Push]',
                                'cost_per_k' => 0.10,
                                'customer_price_per_k' => 0.35,
                                'reseller_price_per_k' => 0.20,
                                'min_quantity' => 500,
                                'max_quantity' => 500000,
                                'has_refill' => false,
                                'refill_days' => 0,
                                'has_cancel' => false,
                                'start_time' => 'Instant',
                                'completion_time' => '100K / Day Fast',
                                'quality_level' => 'Natural View Distribution',
                                'geo_targeting' => 'Global',
                                'dripfeed_supported' => false,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Facebook',
                'slug' => 'facebook',
                'icon' => 'fa-brands fa-facebook',
                'sort_order' => 5,
                'categories' => [
                    [
                        'name' => 'Page Likes & Followers',
                        'slug' => 'facebook-page-likes',
                        'icon' => 'fa-solid fa-thumbs-up',
                        'services' => [
                            [
                                'name' => 'Facebook Page Likes + Followers [Real Accounts | Non Drop]',
                                'cost_per_k' => 2.80,
                                'customer_price_per_k' => 5.50,
                                'reseller_price_per_k' => 3.90,
                                'min_quantity' => 100,
                                'max_quantity' => 50000,
                                'has_refill' => true,
                                'refill_days' => 60,
                                'has_cancel' => true,
                                'start_time' => '0 - 1 Hour',
                                'completion_time' => '5K / Day Steady',
                                'quality_level' => 'Real Active Facebook Accounts',
                                'geo_targeting' => 'Global',
                                'dripfeed_supported' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'X / Twitter',
                'slug' => 'twitter',
                'icon' => 'fa-brands fa-x-twitter',
                'sort_order' => 6,
                'categories' => [
                    [
                        'name' => 'Followers',
                        'slug' => 'twitter-followers',
                        'icon' => 'fa-solid fa-user-check',
                        'services' => [
                            [
                                'name' => 'X / Twitter Followers — High Quality with PFP & Bio',
                                'cost_per_k' => 3.20,
                                'customer_price_per_k' => 6.20,
                                'reseller_price_per_k' => 4.50,
                                'min_quantity' => 100,
                                'max_quantity' => 25000,
                                'has_refill' => true,
                                'refill_days' => 30,
                                'has_cancel' => true,
                                'start_time' => '1 - 3 Hours',
                                'completion_time' => '3K / Day Safe Pace',
                                'quality_level' => 'HQ Aged Profiles with Activity',
                                'geo_targeting' => 'Global Mixed',
                                'dripfeed_supported' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Spotify',
                'slug' => 'spotify',
                'icon' => 'fa-brands fa-spotify',
                'sort_order' => 7,
                'categories' => [
                    [
                        'name' => 'Plays & Followers',
                        'slug' => 'spotify-plays',
                        'icon' => 'fa-solid fa-music',
                        'services' => [
                            [
                                'name' => 'Spotify Track Plays — Royalty Eligible [USA / Tier 1 Streams]',
                                'cost_per_k' => 1.10,
                                'customer_price_per_k' => 2.60,
                                'reseller_price_per_k' => 1.80,
                                'min_quantity' => 500,
                                'max_quantity' => 1000000,
                                'has_refill' => true,
                                'refill_days' => 60,
                                'has_cancel' => false,
                                'start_time' => '2 - 6 Hours',
                                'completion_time' => '10K - 20K / Day',
                                'quality_level' => '100% Premium / Free User Plays',
                                'geo_targeting' => 'USA & Canada Tier 1',
                                'dripfeed_supported' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $serviceIndex = 101;
        foreach ($platformsData as $pData) {
            $platform = SmmPlatform::updateOrCreate(
                ['slug' => $pData['slug']],
                [
                    'name' => $pData['name'],
                    'icon' => $pData['icon'],
                    'sort_order' => $pData['sort_order'],
                    'is_active' => true,
                ]
            );

            foreach ($pData['categories'] as $cIndex => $cData) {
                $category = SmmCategory::updateOrCreate(
                    ['smm_platform_id' => $platform->id, 'slug' => $cData['slug']],
                    [
                        'name' => $cData['name'],
                        'icon' => $cData['icon'] ?? null,
                        'sort_order' => $cIndex + 1,
                        'is_active' => true,
                    ]
                );

                foreach ($cData['services'] as $sData) {
                    SmmService::updateOrCreate(
                        [
                            'smm_platform_id' => $platform->id,
                            'smm_category_id' => $category->id,
                            'name' => $sData['name'],
                        ],
                        [
                            'smm_provider_id' => $mockProvider->id,
                            'provider_service_id' => (string)$serviceIndex,
                            'service_type' => 'default',
                            'description' => "Fast and secure delivery. Provide exact URL or handle. 24/7 automated delivery with live order tracking and refill protection.",
                            'cost_per_k' => $sData['cost_per_k'],
                            'customer_price_per_k' => $sData['customer_price_per_k'],
                            'reseller_price_per_k' => $sData['reseller_price_per_k'],
                            'min_quantity' => $sData['min_quantity'],
                            'max_quantity' => $sData['max_quantity'],
                            'has_refill' => $sData['has_refill'],
                            'refill_days' => $sData['refill_days'],
                            'has_cancel' => $sData['has_cancel'],
                            'start_time' => $sData['start_time'],
                            'completion_time' => $sData['completion_time'],
                            'quality_level' => $sData['quality_level'],
                            'geo_targeting' => $sData['geo_targeting'],
                            'dripfeed_supported' => $sData['dripfeed_supported'],
                            'status' => 'active',
                            'sort_order' => $serviceIndex,
                        ]
                    );
                    $serviceIndex++;
                }
            }
        }

        // 3. Ensure demo Child Panel Agency Organization exists
        $agencyOrg = Organization::firstOrCreate(
            ['slug' => 'apex-agency'],
            [
                'name' => 'Apex Digital SMM Agency',
                'subdomain' => 'apex',
                'custom_domain' => 'panel.apexagency.com',
                'custom_domain_status' => 'active',
                'brand_name' => 'Apex Viral Solutions',
                'email' => 'support@apexagency.com',
                'phone' => '+18005550199',
                'currency' => 'USD',
                'status' => 'active',
                'markup_type' => 'percentage',
                'default_markup' => 35.00,
                'allow_public_registration' => true,
                'theme_config' => [
                    'primary_color' => '#2563EB',
                    'secondary_color' => '#1E40AF',
                    'accent_color' => '#38BDF8',
                    'font' => 'Instrument Sans',
                ],
                'contact_details' => [
                    'email' => 'support@apexagency.com',
                    'whatsapp' => '+1234567890',
                    'telegram' => '@ApexSupport',
                ],
                'terms_content' => "Terms of Service: All services are delivered via verified social media networks according to terms and platform guidelines.",
                'privacy_content' => "Privacy Policy: Customer data is confidential and never shared with third parties.",
            ]
        );

        // 4. Ensure demo users exist
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@zacma.com'],
            [
                'name' => 'Super Administrator',
                'password' => bcrypt('password'),
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ]
        );

        $resellerUser = User::firstOrCreate(
            ['email' => 'reseller@zacma.com'],
            [
                'name' => 'Agency Wholesale Reseller',
                'password' => bcrypt('password'),
                'role' => User::ROLE_RESELLER,
                'is_active' => true,
                'organization_id' => $agencyOrg->id,
            ]
        );

        $customerUser = User::firstOrCreate(
            ['email' => 'customer@zacma.com'],
            [
                'name' => 'Customer Demo User',
                'password' => bcrypt('password'),
                'role' => User::ROLE_CUSTOMER,
                'is_active' => true,
            ]
        );

        // 5. Ensure all existing users have an initialized Wallet with funds
        foreach (User::all() as $user) {
            $initialBalance = $user->isSuperAdmin() ? 5000.00 : ($user->isReseller() ? 1000.00 : 250.00);
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'organization_id' => $user->organization_id,
                    'balance' => $initialBalance,
                    'currency' => 'USD',
                    'total_deposited' => $initialBalance,
                    'total_spent' => 0.0000,
                    'total_refunded' => 0.0000,
                    'status' => 'active',
                ]
            );

            // Create initial deposit ledger transaction if newly created
            if ($wallet->wasRecentlyCreated) {
                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $user->id,
                    'organization_id' => $user->organization_id,
                    'type' => WalletTransaction::TYPE_DEPOSIT,
                    'amount' => $initialBalance,
                    'fee' => 0.0000,
                    'balance_before' => 0.0000,
                    'balance_after' => $initialBalance,
                    'currency' => 'USD',
                    'reference' => 'WAL-INIT-' . strtoupper(Str::random(8)),
                    'description' => 'Initial platform welcome credit deposit',
                    'status' => 'completed',
                ]);
            }

            // Create default API key for admin and resellers
            if ($user->isSuperAdmin() || $user->isReseller() || $user->isStaff() || $user->isOrgAdmin()) {
                if ($user->resellerApiKeys()->count() === 0) {
                    ResellerApiKey::generateForUser($user, 'Primary Production API Key');
                }
            }
        }
    }
}
