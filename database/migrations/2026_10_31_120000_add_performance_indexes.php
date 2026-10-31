<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the queries the admin portal actually runs.
 *
 * - activity_log: every per-user query filters on causer_id alone (the
 *   package's morph index leads with causer_type, so it can't serve them) and
 *   the dashboard/notifications filter and sort by created_at.
 * - sessions: "who is online" and "my login sessions" filter on user_id +
 *   last_activity.
 * - users: the list sorts by created_at and filters on is_active; on
 *   PostgreSQL the ILIKE '%term%' search gets trigram (GIN) indexes.
 * - permissions: role forms group by category.
 *
 * On PostgreSQL this also enables the extensions PLAN.md requires (PostGIS,
 * btree_gist, pg_trgm). Missing extension packages are logged, not fatal, so
 * a database without PostGIS still migrates.
 */
return new class extends Migration
{
    /**
     * A failed CREATE EXTENSION would poison a surrounding PostgreSQL
     * transaction, so each statement runs on its own.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        $pgsql = DB::getDriverName() === 'pgsql';

        if ($pgsql) {
            foreach (['pg_trgm', 'btree_gist', 'postgis'] as $extension) {
                $this->enableExtension($extension);
            }
        }

        $this->addIndex('activity_log', ['created_at'], 'activity_log_created_at_index');
        $this->addIndex('activity_log', ['causer_id', 'created_at'], 'activity_log_causer_created_index');
        $this->addIndex('activity_log', ['causer_id', 'log_name'], 'activity_log_causer_log_name_index');

        $this->addIndex('sessions', ['user_id', 'last_activity'], 'sessions_user_last_activity_index');

        $this->addIndex('users', ['created_at'], 'users_created_at_index');
        $this->addIndex('users', ['is_active'], 'users_is_active_index');

        if (Schema::hasColumn('permissions', 'category')) {
            $this->addIndex('permissions', ['category'], 'permissions_category_index');
        }

        if ($pgsql && $this->extensionInstalled('pg_trgm')) {
            foreach (['name', 'email', 'username'] as $column) {
                DB::statement("CREATE INDEX IF NOT EXISTS users_{$column}_trgm_index ON users USING gin ({$column} gin_trgm_ops)");
            }
        }
    }

    public function down(): void
    {
        $drops = [
            'activity_log' => ['activity_log_created_at_index', 'activity_log_causer_created_index', 'activity_log_causer_log_name_index'],
            'sessions' => ['sessions_user_last_activity_index'],
            'users' => ['users_created_at_index', 'users_is_active_index'],
            'permissions' => ['permissions_category_index'],
        ];

        foreach ($drops as $table => $indexes) {
            foreach ($indexes as $index) {
                if (Schema::hasIndex($table, $index)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($index));
                }
            }
        }

        if (DB::getDriverName() === 'pgsql') {
            foreach (['name', 'email', 'username'] as $column) {
                DB::statement("DROP INDEX IF EXISTS users_{$column}_trgm_index");
            }
        }
    }

    /** @param list<string> $columns */
    private function addIndex(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
    }

    private function enableExtension(string $extension): void
    {
        try {
            DB::statement("CREATE EXTENSION IF NOT EXISTS {$extension}");
        } catch (\Throwable $e) {
            Log::warning("Extension {$extension} could not be enabled: " . $e->getMessage());
        }
    }

    private function extensionInstalled(string $extension): bool
    {
        return DB::table('pg_extension')->where('extname', $extension)->exists();
    }
};
