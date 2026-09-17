<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();

            $table->string('name');
            $table->string('api_key_hash')->unique(); // SHA-256 hash of plaintext key
            $table->string('key_prefix'); // zac_live_... for identification
            $table->integer('rate_limit_per_minute')->default(120);
            $table->json('ip_whitelist')->nullable();
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['api_key_hash']);
        });

        Schema::create('smm_bulk_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();

            $table->integer('total_rows')->default(0);
            $table->integer('valid_rows')->default(0);
            $table->integer('invalid_rows')->default(0);
            $table->integer('successful_orders')->default(0);
            $table->integer('failed_orders')->default(0);
            $table->decimal('total_cost', 14, 4)->default(0.0000);
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->json('report_log')->nullable();
            $table->timestamps();
        });

        Schema::create('bulk_customer_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();

            $table->string('file_name');
            $table->integer('total_rows')->default(0);
            $table->integer('valid_rows')->default(0);
            $table->integer('invalid_rows')->default(0);
            $table->integer('duplicate_rows')->default(0);
            $table->integer('imported_count')->default(0);
            $table->string('status')->default('completed');
            $table->json('error_report')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_customer_imports');
        Schema::dropIfExists('smm_bulk_orders');
        Schema::dropIfExists('reseller_api_keys');
    }
};
