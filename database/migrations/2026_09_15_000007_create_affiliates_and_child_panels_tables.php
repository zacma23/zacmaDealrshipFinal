<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('referral_code');
            $table->decimal('commission_rate', 5, 2)->default(5.00); // 5%
            $table->decimal('total_earned', 14, 4)->default(0.0000);
            $table->timestamps();

            $table->index(['referrer_id', 'referral_code']);
        });

        Schema::create('affiliate_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 14, 4);
            $table->string('status')->default('pending'); // pending, approved, paid, rejected
            $table->string('payout_method')->default('wallet');
            $table->string('payout_account')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Add white-label child-panel fields to organizations
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'brand_name')) {
                $table->string('brand_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('organizations', 'theme_config')) {
                $table->json('theme_config')->nullable()->after('branding_colors');
            }
            if (!Schema::hasColumn('organizations', 'contact_details')) {
                $table->json('contact_details')->nullable()->after('country');
            }
            if (!Schema::hasColumn('organizations', 'terms_content')) {
                $table->longText('terms_content')->nullable();
            }
            if (!Schema::hasColumn('organizations', 'privacy_content')) {
                $table->longText('privacy_content')->nullable();
            }
            if (!Schema::hasColumn('organizations', 'markup_type')) {
                $table->string('markup_type')->default('percentage'); // percentage, fixed
            }
            if (!Schema::hasColumn('organizations', 'default_markup')) {
                $table->decimal('default_markup', 8, 2)->default(25.00); // +25% default
            }
            if (!Schema::hasColumn('organizations', 'allow_public_registration')) {
                $table->boolean('allow_public_registration')->default(true);
            }
            if (!Schema::hasColumn('organizations', 'custom_domain_status')) {
                $table->string('custom_domain_status')->default('inactive'); // inactive, pending_dns, active
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $columns = [
                'brand_name', 'theme_config', 'contact_details', 'terms_content',
                'privacy_content', 'markup_type', 'default_markup',
                'allow_public_registration', 'custom_domain_status'
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('organizations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('affiliate_payouts');
        Schema::dropIfExists('affiliate_referrals');
    }
};
