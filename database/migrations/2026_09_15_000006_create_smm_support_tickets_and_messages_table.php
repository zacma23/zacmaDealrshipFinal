<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smm_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('smm_order_id')->nullable()->constrained('smm_orders')->nullOnDelete();

            $table->string('subject');
            $table->string('category')->default('order'); // order, payment, service, bug, other
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('status')->default('open'); // open, in_progress, answered, closed
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('last_reply_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index(['ticket_number']);
        });

        Schema::create('smm_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smm_ticket_id')->constrained('smm_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->text('message');
            $table->boolean('is_staff')->default(false);
            $table->boolean('is_internal_note')->default(false);
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['smm_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smm_ticket_messages');
        Schema::dropIfExists('smm_tickets');
    }
};
