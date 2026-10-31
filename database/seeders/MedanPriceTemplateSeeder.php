<?php

namespace Database\Seeders;

use App\Actions\Vehicle\MasterDataCache;
use App\Models\PriceTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * "Tarif Pasar Medan 2026" (PLAN.md 9) from database/data/medan_price_template.php.
 *
 * Services get one row per vehicle category. Sparepart sample products are
 * stored with module "sparepart", no category, grouped per vehicle type.
 * Re-running replaces the template's items so the data file stays the source
 * of truth.
 */
class MedanPriceTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('data/medan_price_template.php');

        DB::transaction(function () use ($data) {
            $template = PriceTemplate::updateOrCreate(
                ['name' => $data['name']],
                ['city' => $data['city'], 'disclaimer' => $data['disclaimer'], 'is_active' => true],
            );

            $template->items()->delete();

            $now = now();
            $rows = [];

            foreach ($data['items'] as $item) {
                foreach ($item['prices'] as $categoryCode => $price) {
                    $rows[] = [
                        'price_template_id' => $template->id,
                        'module_code' => $item['module'],
                        'group_name' => $item['group'],
                        'service_name' => $item['service'],
                        'vehicle_category_code' => $categoryCode,
                        'price' => (int) $price,
                        'duration_minutes' => $item['duration'] ?? null,
                        'is_addon' => (bool) ($item['addon'] ?? false),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach ($data['products'] as $product) {
                $rows[] = [
                    'price_template_id' => $template->id,
                    'module_code' => 'sparepart',
                    'group_name' => 'Sparepart ' . ucfirst($product['vehicle_type']),
                    'service_name' => $product['name'],
                    'vehicle_category_code' => null,
                    'price' => (int) $product['price'],
                    'duration_minutes' => null,
                    'is_addon' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('price_template_items')->insert($chunk);
            }
        });

        MasterDataCache::flush();
    }
}
