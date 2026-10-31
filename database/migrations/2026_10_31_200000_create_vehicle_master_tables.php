<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform-wide vehicle master data (PLAN.md 4.3), managed by superadmin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 50);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestampsTz();
        });

        Schema::create('vehicle_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->string('name', 100);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['vehicle_type_id', 'sort']);
        });

        Schema::create('vehicle_brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->restrictOnDelete();
            $table->string('name', 80);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['vehicle_type_id', 'name']);
        });

        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_brand_id')->constrained('vehicle_brands')->restrictOnDelete();
            $table->string('name', 100);
            $table->foreignId('default_category_id')->nullable()->constrained('vehicle_categories')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['vehicle_brand_id', 'name']);
            $table->index('default_category_id');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('vehicle_brands');
        Schema::dropIfExists('vehicle_categories');
        Schema::dropIfExists('vehicle_types');
    }
};
