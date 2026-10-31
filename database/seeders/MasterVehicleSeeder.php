<?php

namespace Database\Seeders;

use App\Actions\Vehicle\MasterDataCache;
use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Vehicle types, pricing categories (PLAN.md 4.3) and popular Indonesian
 * brands/models with their default category. Idempotent.
 */
class MasterVehicleSeeder extends Seeder
{
    /** brand => [model => default category code] */
    private const MOTOR = [
        'Honda' => [
            'BeAT' => 'motor_matic',
            'Vario' => 'motor_matic',
            'Scoopy' => 'motor_matic',
            'PCX 160' => 'motor_matic',
            'Supra X 125' => 'motor_bebek',
            'CBR150R' => 'motor_sport',
        ],
        'Yamaha' => [
            'NMAX' => 'motor_matic',
            'Aerox' => 'motor_matic',
            'Mio' => 'motor_matic',
            'Jupiter Z' => 'motor_bebek',
            'R15' => 'motor_sport',
        ],
        'Suzuki' => [
            'Satria F150' => 'motor_sport',
        ],
        'Kawasaki' => [
            'Ninja 250' => 'motor_sport',
            'KLX 150' => 'motor_sport',
        ],
    ];

    private const MOBIL = [
        'Toyota' => [
            'Avanza' => 'car_mpv',
            'Innova' => 'car_mpv',
            'Calya' => 'car_mpv',
            'Agya' => 'car_city',
            'Rush' => 'car_suv',
            'Fortuner' => 'car_suv',
            'Hilux' => 'car_pickup',
            'HiAce' => 'car_van',
        ],
        'Daihatsu' => [
            'Xenia' => 'car_mpv',
            'Ayla' => 'car_city',
            'Terios' => 'car_suv',
            'Gran Max' => 'car_van',
        ],
        'Honda' => [
            'Brio' => 'car_city',
            'City' => 'car_sedan',
            'Mobilio' => 'car_mpv',
            'HR-V' => 'car_suv',
            'CR-V' => 'car_suv',
        ],
        'Suzuki' => [
            'Ertiga' => 'car_mpv',
            'XL7' => 'car_suv',
            'Carry' => 'car_pickup',
        ],
        'Mitsubishi' => [
            'Xpander' => 'car_mpv',
            'Pajero Sport' => 'car_suv',
            'L300' => 'car_pickup',
        ],
        'Isuzu' => [
            'Elf' => 'car_van',
        ],
        'BMW' => [
            '3 Series' => 'car_premium',
        ],
        'Mercedes-Benz' => [
            'C-Class' => 'car_premium',
        ],
    ];

    public function run(): void
    {
        $template = require database_path('data/medan_price_template.php');

        DB::transaction(function () use ($template) {
            $types = [];
            foreach ([['motor', 'Motor', 1], ['mobil', 'Mobil', 2]] as [$code, $name, $sort]) {
                $types[$code] = VehicleType::updateOrCreate(['code' => $code], ['name' => $name, 'sort' => $sort]);
            }

            $categories = [];
            foreach ($template['vehicle_categories'] as $i => $row) {
                $categories[$row['code']] = VehicleCategory::withTrashed()->updateOrCreate(
                    ['code' => $row['code']],
                    ['vehicle_type_id' => $types[$row['type']]->id, 'name' => $row['name'], 'sort' => $i + 1, 'deleted_at' => null],
                );
            }

            foreach (['motor' => self::MOTOR, 'mobil' => self::MOBIL] as $type => $brands) {
                foreach ($brands as $brandName => $models) {
                    $brand = VehicleBrand::withTrashed()->updateOrCreate(
                        ['vehicle_type_id' => $types[$type]->id, 'name' => $brandName],
                        ['deleted_at' => null],
                    );

                    foreach ($models as $modelName => $categoryCode) {
                        VehicleModel::withTrashed()->updateOrCreate(
                            ['vehicle_brand_id' => $brand->id, 'name' => $modelName],
                            ['default_category_id' => $categories[$categoryCode]->id, 'deleted_at' => null],
                        );
                    }
                }
            }
        });

        MasterDataCache::flush();
    }
}
