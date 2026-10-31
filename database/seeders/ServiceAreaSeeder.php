<?php

namespace Database\Seeders;

use App\Actions\Vehicle\MasterDataCache;
use App\Models\ServiceArea;
use Illuminate\Database\Seeder;

/** The 21 kecamatan of Kota Medan (PLAN.md 9.7). Idempotent. */
class ServiceAreaSeeder extends Seeder
{
    public function run(): void
    {
        $template = require database_path('data/medan_price_template.php');

        foreach ($template['service_areas'] as $district) {
            ServiceArea::updateOrCreate(
                ['city' => $template['city'], 'district' => $district],
                ['is_active' => true],
            );
        }

        MasterDataCache::flush();
    }
}
