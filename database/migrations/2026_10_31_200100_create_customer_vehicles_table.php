<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vehicles registered by a customer ("Kendaraan Saya").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_vehicles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_model_id')->nullable()->constrained('vehicle_models')->nullOnDelete();
            $table->foreignId('vehicle_category_id')->constrained('vehicle_categories')->restrictOnDelete();
            $table->string('plate_number', 20);
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color', 40)->nullable();
            $table->string('nickname', 60)->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['user_id', 'created_at']);
            $table->index('vehicle_category_id');
            $table->index('vehicle_model_id');
        });

        // A plate can only be registered once per customer among live rows.
        // Partial indexes are supported by both PostgreSQL and SQLite.
        DB::statement(
            'CREATE UNIQUE INDEX customer_vehicles_user_plate_unique '
            . 'ON customer_vehicles (user_id, plate_number) WHERE deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_vehicles');
    }
};
