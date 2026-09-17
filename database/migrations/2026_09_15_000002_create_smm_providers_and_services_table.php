<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smm_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('api_url');
            $table->text('api_key');
            $table->string('adapter_type')->default('standard_v2'); // standard_v2, mock_sandbox, etc.
            $table->string('status')->default('active'); // active, disabled, error
            $table->decimal('balance', 14, 4)->default(0.0000);
            $table->string('currency', 3)->default('USD');
            $table->integer('priority')->default(1);
            $table->foreignId('fallback_provider_id')->nullable()->constrained('smm_providers')->nullOnDelete();
            $table->timestamp('last_sync_at')->nullable();
            $table->string('health_status')->default('healthy'); // healthy, degraded, down
            $table->text('error_log')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
        });

        Schema::create('smm_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smm_platform_id')->constrained('smm_platforms')->cascadeOnDelete();
            $table->foreignId('smm_category_id')->constrained('smm_categories')->cascadeOnDelete();
            $table->foreignId('smm_provider_id')->nullable()->constrained('smm_providers')->nullOnDelete();
            $table->string('provider_service_id')->nullable();

            $table->string('name');
            $table->string('service_type')->default('default'); // default, custom_comments, package, drip_feed
            $table->text('description')->nullable();

            // Financial & Pricing (Price per 1,000 units)
            $table->decimal('cost_per_k', 12, 4)->default(0.0000);
            $table->decimal('customer_price_per_k', 12, 4)->default(0.0000);
            $table->decimal('reseller_price_per_k', 12, 4)->default(0.0000);

            // Quantities & Policies
            $table->unsignedInteger('min_quantity')->default(10);
            $table->unsignedInteger('max_quantity')->default(100000);
            $table->boolean('has_refill')->default(false);
            $table->integer('refill_days')->default(0);
            $table->boolean('has_cancel')->default(false);

            // Metrics & Targeting
            $table->string('start_time')->default('0 - 1 Hour');
            $table->string('completion_time')->default('24 Hours');
            $table->string('quality_level')->default('High Quality');
            $table->string('geo_targeting')->default('Global');
            $table->boolean('dripfeed_supported')->default(false);

            // Status & Display
            $table->string('status')->default('active'); // active, disabled, maintenance
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['smm_platform_id', 'smm_category_id', 'status']);
            $table->index(['smm_provider_id', 'provider_service_id']);
            $table->index(['status', 'sort_order']);
        });

        Schema::create('smm_tenant_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('smm_service_id')->constrained('smm_services')->cascadeOnDelete();

            $table->string('custom_name')->nullable();
            $table->text('custom_description')->nullable();
            $table->decimal('custom_price_per_k', 12, 4)->nullable();
            $table->unsignedInteger('min_quantity')->nullable();
            $table->unsignedInteger('max_quantity')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'smm_service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smm_tenant_services');
        Schema::dropIfExists('smm_services');
        Schema::dropIfExists('smm_providers');
    }
};
