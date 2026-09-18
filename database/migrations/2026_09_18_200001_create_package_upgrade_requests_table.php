<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('package_upgrade_requests')) {
            Schema::create('package_upgrade_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('current_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
                $table->foreignId('requested_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->decimal('price', 12, 2);
                $table->string('currency', 10)->default('ETB');
                $table->string('billing_cycle', 20)->default('monthly'); // monthly, quarterly, yearly
                $table->string('payment_gateway', 50)->default('chapa');
                $table->string('payment_reference', 100)->nullable()->index();
                $table->text('payment_url')->nullable();
                $table->string('payment_status', 30)->default('pending'); // pending, paid, failed, cancelled, verified
                $table->timestamp('paid_at')->nullable();
                $table->string('approval_status', 30)->default('pending'); // pending, approved, rejected
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'approval_status']);
                $table->index(['approval_status', 'payment_status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('package_upgrade_requests');
    }
};

