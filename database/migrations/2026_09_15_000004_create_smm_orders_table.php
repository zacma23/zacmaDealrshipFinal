<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smm_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('smm_service_id')->constrained('smm_services')->cascadeOnDelete();
            $table->foreignId('smm_provider_id')->nullable()->constrained('smm_providers')->nullOnDelete();
            $table->string('provider_order_id')->nullable();

            // SMM Parameters
            $table->string('target'); // Link, @username, channel, post ID
            $table->unsignedInteger('quantity');
            $table->decimal('charge', 14, 4); // User charged amount
            $table->decimal('cost', 14, 4)->default(0.0000); // Provider wholesale cost
            $table->decimal('profit', 14, 4)->default(0.0000); // Net profit
            $table->string('currency', 3)->default('USD');

            // Provider Sync Metrics
            $table->integer('start_count')->nullable();
            $table->integer('remains')->nullable();
            $table->string('status')->default('pending'); // pending, processing, in_progress, completed, partial, cancelled, failed, refunded

            // Drip-Feed & Refill
            $table->boolean('is_drip_feed')->default(false);
            $table->integer('drip_runs')->nullable();
            $table->integer('drip_interval')->nullable(); // minutes
            $table->integer('drip_delivered')->default(0);
            $table->boolean('has_refill')->default(false);
            $table->string('refill_status')->default('none'); // none, pending, completed, rejected

            // API & Context
            $table->text('error_message')->nullable();
            $table->boolean('is_api_order')->default(false);
            $table->unsignedBigInteger('reseller_api_key_id')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index(['smm_provider_id', 'provider_order_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smm_orders');
    }
};
