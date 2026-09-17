<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Extend profiles for Dealer & Business seller support
        Schema::table('profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('profiles', 'account_type')) {
                $table->string('account_type')->default('individual'); // individual, business, dealer
            }
            if (!Schema::hasColumn('profiles', 'business_name')) {
                $table->string('business_name')->nullable();
            }
            if (!Schema::hasColumn('profiles', 'license_number')) {
                $table->string('license_number')->nullable();
            }
            if (!Schema::hasColumn('profiles', 'tin_number')) {
                $table->string('tin_number')->nullable();
            }
            if (!Schema::hasColumn('profiles', 'address')) {
                $table->string('address')->nullable();
            }
            if (!Schema::hasColumn('profiles', 'website')) {
                $table->string('website')->nullable();
            }
        });

        // 2. Extend subscription plans for Quarterly and Yearly billing
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_plans', 'price_quarterly')) {
                $table->decimal('price_quarterly', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('subscription_plans', 'price_yearly')) {
                $table->decimal('price_yearly', 10, 2)->nullable();
            }
        });

        // 3. Extend subscriptions for billing cycle and gateway
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'billing_cycle')) {
                $table->string('billing_cycle')->default('monthly'); // monthly, quarterly, yearly
            }
            if (!Schema::hasColumn('subscriptions', 'gateway')) {
                $table->string('gateway')->default('chapa'); // chapa, telebirr, cbe, ebirr, santimpay
            }
        });

        // 4. Extend crm_leads for notes and follow-ups
        Schema::table('crm_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_leads', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('crm_leads', 'follow_up_date')) {
                $table->timestamp('follow_up_date')->nullable();
            }
        });

        // 5. Buyer Requirements board (CRM)
        if (!Schema::hasTable('buyer_requirements')) {
            Schema::create('buyer_requirements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('type')->default('vehicle'); // vehicle, real_estate, apartment, product
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('city')->default('Addis Ababa');
                $table->decimal('budget_min', 14, 2)->nullable();
                $table->decimal('budget_max', 14, 2)->nullable();
                $table->string('currency', 3)->default('ETB');
                $table->json('specifications')->nullable();
                $table->string('status')->default('open'); // open, fulfilled, closed
                $table->timestamps();

                $table->index(['type', 'status']);
                $table->index(['user_id', 'status']);
            });
        }

        // 6. Reviews & Ratings system
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // reviewer
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('listing_id')->nullable()->constrained('listings')->cascadeOnDelete();
                $table->unsignedTinyInteger('rating')->default(5); // 1-5
                $table->text('comment')->nullable();
                $table->boolean('is_approved')->default(true);
                $table->timestamps();

                $table->index(['seller_id', 'is_approved']);
                $table->index(['listing_id', 'is_approved']);
            });
        }

        // 7. Messaging System (In-app communication between buyers and sellers)
        if (!Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
                $table->text('message');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['sender_id', 'recipient_id']);
                $table->index(['recipient_id', 'read_at']);
            });
        }

        // 8. Payment Gateways Configuration
        if (!Schema::hasTable('payment_gateways')) {
            Schema::create('payment_gateways', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique(); // chapa, telebirr, cbe, ebirr, santimpay
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_test_mode')->default(true);
                $table->json('credentials')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('buyer_requirements');

        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropColumn(['notes', 'follow_up_date']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['billing_cycle', 'gateway']);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['price_quarterly', 'price_yearly']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['account_type', 'business_name', 'license_number', 'tin_number', 'address', 'website']);
        });
    }
};

