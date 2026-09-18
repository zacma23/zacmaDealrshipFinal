<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vehicle_brands')) {
            Schema::create('vehicle_brands', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('vehicle_models')) {
            Schema::create('vehicle_models', function (Blueprint $table) {
                $table->id();
                $table->foreignId('brand_id')->constrained('vehicle_brands')->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->string('body_type')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['brand_id', 'slug']);
                $table->index(['brand_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('vehicle_brands');
    }
};
