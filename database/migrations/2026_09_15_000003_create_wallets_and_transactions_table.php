<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->string('provider'); // santimpay, telebirr, chapa, paypal, card, crypto, cash
                $table->decimal('amount', 14, 4);
                $table->string('currency', 3)->default('USD');
                $table->string('transaction_reference')->unique();
                $table->string('provider_reference')->nullable();
                $table->string('status')->default('pending'); // pending, verified, failed, refunded
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['transaction_reference']);
                $table->index(['provider_reference']);
            });
        }

        if (!Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();

                $table->decimal('balance', 14, 4)->default(0.0000);
                $table->string('currency', 3)->default('USD');
                $table->decimal('total_deposited', 14, 4)->default(0.0000);
                $table->decimal('total_spent', 14, 4)->default(0.0000);
                $table->decimal('total_refunded', 14, 4)->default(0.0000);
                $table->string('status')->default('active'); // active, frozen
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['organization_id']);
            });
        }

        if (!Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();

                $table->string('type'); // deposit, order_charge, order_refund, manual_credit, manual_debit, affiliate_commission
                $table->decimal('amount', 14, 4);
                $table->decimal('fee', 14, 4)->default(0.0000);
                $table->decimal('balance_before', 14, 4);
                $table->decimal('balance_after', 14, 4);
                $table->string('currency', 3)->default('USD');
                $table->string('reference')->unique();

                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->unsignedBigInteger('smm_order_id')->nullable(); // linked SMM order if charge/refund

                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->string('status')->default('completed'); // completed, pending, failed
                $table->timestamps();

                $table->index(['wallet_id', 'type']);
                $table->index(['user_id', 'created_at']);
                $table->index(['reference']);
                $table->index(['smm_order_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('payments');
    }
};
