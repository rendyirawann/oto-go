<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Default price templates from the platform (PLAN.md 4.4 / 9), e.g.
 * "Tarif Pasar Medan 2026". Owners copy them into their own catalogue.
 * Money is integer rupiah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('city', 80)->nullable();
            $table->text('disclaimer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index(['is_active', 'city']);
        });

        Schema::create('price_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_template_id')->constrained('price_templates')->cascadeOnDelete();
            $table->string('module_code', 20);
            $table->string('group_name', 80);
            $table->string('service_name', 150);
            $table->string('vehicle_category_code', 40)->nullable();
            $table->bigInteger('price');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->boolean('is_addon')->default(false);
            $table->timestampsTz();

            $table->index(['price_template_id', 'vehicle_category_code']);
            $table->index(['price_template_id', 'module_code']);
            $table->index('module_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_template_items');
        Schema::dropIfExists('price_templates');
    }
};
