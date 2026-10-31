<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cities / districts (kecamatan) where OtoGo operates. Polygon geometry is
 * added in the logistics phase together with PostGIS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_areas', function (Blueprint $table) {
            $table->id();
            $table->string('city', 80);
            $table->string('district', 80);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['city', 'district']);
            $table->index(['is_active', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_areas');
    }
};
