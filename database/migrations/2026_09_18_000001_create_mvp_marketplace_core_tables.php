<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Profiles (auto-created for every user)
        if (!Schema::hasTable('profiles')) {
            Schema::create('profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('name')->nullable();
                $table->string('phone')->nullable();
                $table->string('city')->nullable()->default('Addis Ababa');
                $table->text('bio')->nullable();
                $table->string('photo')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->timestamps();
            });
        }

        // 2. Roles & Permissions (simple 3-role RBAC: super_admin, user)
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique(); // super_admin, user
                $table->string('display_name');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('display_name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'role_id']);
            });
        }

        // 3. Categories (flat list, no nested subcategories in MVP)
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('type')->nullable(); // vehicle, real_estate, apartment
                $table->string('icon')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['type', 'is_active']);
            });
        } else {
            Schema::table('categories', function (Blueprint $table) {
                if (!Schema::hasColumn('categories', 'type')) {
                    $table->string('type')->nullable()->after('slug');
                }
                if (!Schema::hasColumn('categories', 'icon')) {
                    $table->string('icon')->nullable()->after('type');
                }
            });
        }

        // 4. Subscription Plans (3 fixed plans: Basic, Premium, Pro)
        if (!Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // Basic, Premium, Pro
                $table->string('slug')->unique();
                $table->decimal('price', 10, 2)->default(0.00);
                $table->string('currency', 3)->default('ETB');
                $table->integer('listing_limit')->default(20);
                $table->string('billing_period')->default('monthly');
                $table->json('features')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        } else {
            Schema::table('subscription_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('subscription_plans', 'billing_period')) {
                    $table->string('billing_period')->default('monthly')->after('listing_limit');
                }
            });
        }

        // 5. Subscriptions
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
                $table->string('status')->default('active'); // active, expired, cancelled
                $table->timestamp('starts_at');
                $table->timestamp('ends_at');
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        } else {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (!Schema::hasColumn('subscriptions', 'plan_id')) {
                    $table->foreignId('plan_id')->nullable()->after('id')->constrained('subscription_plans')->nullOnDelete();
                }
                if (!Schema::hasColumn('subscriptions', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('subscriptions', 'starts_at')) {
                    $table->timestamp('starts_at')->nullable()->after('status');
                }
                if (!Schema::hasColumn('subscriptions', 'ends_at')) {
                    $table->timestamp('ends_at')->nullable()->after('starts_at');
                }
            });
        }

        // 6. Listings (Single table with JSONB listing_attributes)
        if (!Schema::hasTable('listings')) {
            Schema::create('listings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->string('type'); // vehicle, real_estate, apartment
                $table->string('title');
                $table->string('slug')->index();
                $table->text('description')->nullable();
                $table->decimal('price', 14, 2)->default(0.00);
                $table->string('currency', 3)->default('ETB');
                $table->string('city')->default('Addis Ababa');
                $table->string('address')->nullable();
                $table->string('status')->default('draft'); // draft, pending, published, rejected, sold, rented, expired
                $table->text('rejection_reason')->nullable();
                $table->integer('year')->nullable(); // vehicle year filter
                $table->integer('bedrooms')->nullable(); // real estate / apartment bedroom filter
                $table->json('listing_attributes')->nullable(); // type-specific JSONB attributes
                $table->integer('views_count')->default(0);
                $table->boolean('featured')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'type']);
                $table->index(['user_id', 'status']);
                $table->index(['type', 'price']);
                $table->index(['city', 'status']);
                $table->index('year');
                $table->index('bedrooms');
            });
        } else {
            Schema::table('listings', function (Blueprint $table) {
                if (!Schema::hasColumn('listings', 'type')) {
                    $table->string('type')->default('vehicle')->after('category_id');
                }
                if (!Schema::hasColumn('listings', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable()->after('status');
                }
                if (!Schema::hasColumn('listings', 'year')) {
                    $table->integer('year')->nullable()->after('rejection_reason');
                }
                if (!Schema::hasColumn('listings', 'bedrooms')) {
                    $table->integer('bedrooms')->nullable()->after('year');
                }
                if (!Schema::hasColumn('listings', 'listing_attributes')) {
                    $table->json('listing_attributes')->nullable()->after('bedrooms');
                }
                if (!Schema::hasColumn('listings', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('featured');
                }
            });
        }

        // 7. Listing Images
        if (!Schema::hasTable('listing_images')) {
            Schema::create('listing_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
                $table->string('image_path');
                $table->boolean('is_primary')->default(false);
                $table->integer('order')->default(0);
                $table->timestamps();

                $table->index(['listing_id', 'is_primary']);
            });
        }

        // 8. Inquiries
        if (!Schema::hasTable('inquiries')) {
            Schema::create('inquiries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // buyer
                $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete(); // listing owner
                $table->string('name');
                $table->string('email');
                $table->string('phone')->nullable();
                $table->text('message');
                $table->timestamps();

                $table->index(['seller_id', 'created_at']);
                $table->index(['listing_id']);
            });
        }

        // 9. CRM Leads (Inbound inquiries create a CRM lead for owner with 3 pipeline stages)
        if (!Schema::hasTable('crm_leads')) {
            Schema::create('crm_leads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inquiry_id')->nullable()->constrained('inquiries')->nullOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // listing owner / seller
                $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
                $table->string('buyer_name');
                $table->string('buyer_email');
                $table->string('buyer_phone')->nullable();
                $table->text('message');
                $table->string('status')->default('New'); // New, Contacted, Closed
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['listing_id', 'status']);
            });
        }

        // 10. Favorites
        if (!Schema::hasTable('favorites')) {
            Schema::create('favorites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'listing_id']);
            });
        }

        // 11. Laravel Notifications table
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('crm_leads');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('listing_images');
        Schema::dropIfExists('listings');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('profiles');
    }
};
