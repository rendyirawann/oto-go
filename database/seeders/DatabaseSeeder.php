<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seeders register themselves here in dependency order. Each one is
     * idempotent (firstOrCreate / updateOrCreate) so `db:seed` can be re-run.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SuperAdminSeeder::class,
            MasterVehicleSeeder::class,
            ServiceAreaSeeder::class,
            MedanPriceTemplateSeeder::class,
            BlogSeeder::class,
        ]);
    }
}
