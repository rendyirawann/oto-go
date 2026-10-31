<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * cybercog/laravel-ban ships its bans table with integer morph columns, but
 * users here have UUID primary keys - so banning a user (or eager loading
 * their bans) failed with "invalid input syntax for type bigint".
 *
 * The morph id columns become UUID-compatible strings. Existing rows are
 * kept: their ids are cast to text.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bans')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE bans ALTER COLUMN bannable_id TYPE varchar(36) USING bannable_id::text');
            DB::statement('ALTER TABLE bans ALTER COLUMN created_by_id TYPE varchar(36) USING created_by_id::text');

            return;
        }

        // SQLite / MySQL: rebuild the table with UUID morphs (only test or
        // fresh databases reach this branch).
        $rows = DB::table('bans')->get()->map(fn ($r) => (array) $r)->all();

        Schema::drop('bans');
        Schema::create('bans', function (Blueprint $table) {
            $table->increments('id');
            $table->uuidMorphs('bannable');
            $table->nullableUuidMorphs('created_by');
            $table->text('comment')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('expired_at');
        });

        if ($rows !== []) {
            DB::table('bans')->insert($rows);
        }
    }

    public function down(): void
    {
        // Not reversible without losing UUID ids.
    }
};
