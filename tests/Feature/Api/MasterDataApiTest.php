<?php

namespace Tests\Feature\Api;

use App\Actions\Vehicle\MasterDataCache;
use App\Models\VehicleBrand;
use Database\Seeders\MasterVehicleSeeder;
use Database\Seeders\MedanPriceTemplateSeeder;
use Database\Seeders\ServiceAreaSeeder;
use Illuminate\Support\Facades\DB;

class MasterDataApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([MasterVehicleSeeder::class, ServiceAreaSeeder::class, MedanPriceTemplateSeeder::class]);
    }

    public function test_seeders_are_idempotent(): void
    {
        $this->seed([MasterVehicleSeeder::class, ServiceAreaSeeder::class, MedanPriceTemplateSeeder::class]);

        $this->assertSame(2, DB::table('vehicle_types')->count());
        $this->assertSame(11, DB::table('vehicle_categories')->count());
        $this->assertSame(21, DB::table('service_areas')->count());
        $this->assertSame(1, DB::table('price_templates')->count());
    }

    public function test_vehicle_types(): void
    {
        $this->getJson('/api/v1/vehicle-types')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.code', 'motor')
            ->assertJsonPath('data.1.code', 'mobil');
    }

    public function test_vehicle_categories_with_type_filter(): void
    {
        $this->getJson('/api/v1/vehicle-categories')->assertOk()->assertJsonCount(11, 'data');

        $this->getJson('/api/v1/vehicle-categories?type=motor')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.code', 'motor_matic')
            ->assertJsonPath('data.0.vehicle_type', 'motor');

        // Unknown type is ignored rather than erroring.
        $this->getJson('/api/v1/vehicle-categories?type=pesawat')->assertOk()->assertJsonCount(11, 'data');
    }

    public function test_vehicle_brands_and_models(): void
    {
        $this->getJson('/api/v1/vehicle-brands?type=mobil')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Toyota', 'vehicle_type' => 'mobil'])
            ->assertJsonMissing(['name' => 'Yamaha']);

        $toyota = VehicleBrand::whereHas('type', fn ($q) => $q->where('code', 'mobil'))->where('name', 'Toyota')->firstOrFail();

        $this->getJson('/api/v1/vehicle-models?brand_id=' . $toyota->id . '&q=avan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Avanza')
            ->assertJsonPath('data.0.brand_name', 'Toyota')
            ->assertJsonPath('data.0.default_category.code', 'car_mpv');
    }

    public function test_service_areas(): void
    {
        $this->getJson('/api/v1/service-areas')
            ->assertOk()
            ->assertJsonCount(21, 'data')
            ->assertJsonFragment(['city' => 'Medan', 'district' => 'Medan Baru']);
    }

    public function test_price_estimates(): void
    {
        $response = $this->getJson('/api/v1/price-estimates?vehicle_category=car_mpv&module=carwash')
            ->assertOk()
            ->assertJsonPath('meta.template', 'Tarif Pasar Medan 2026')
            ->assertJsonStructure(['data' => [['module', 'group', 'service', 'vehicle_category_code', 'price', 'duration_minutes', 'is_addon']], 'meta' => ['template', 'disclaimer']]);

        $rows = collect($response->json('data'));
        $this->assertTrue($rows->every(fn ($r) => $r['vehicle_category_code'] === 'car_mpv' && $r['module'] === 'carwash'));
        $this->assertSame(45000, $rows->firstWhere('service', 'Cuci reguler (cuci salju)')['price']);

        $all = $this->getJson('/api/v1/price-estimates')->assertOk()->json('data');
        $this->assertGreaterThan(100, count($all));

        $parts = $this->getJson('/api/v1/price-estimates?vehicle_category=motor_matic&module=sparepart')->assertOk()->json('data');
        $this->assertNotEmpty($parts);
        $this->assertTrue(collect($parts)->every(fn ($r) => $r['module'] === 'sparepart' && $r['vehicle_category_code'] === null));
        $this->assertContains('Busi motor', array_column($parts, 'service'));
    }

    public function test_master_data_is_cached_until_flushed(): void
    {
        $this->getJson('/api/v1/service-areas')->assertJsonCount(21, 'data');

        DB::table('service_areas')->where('district', 'Medan Baru')->delete();

        // Still served from cache.
        $this->getJson('/api/v1/service-areas')->assertJsonCount(21, 'data');

        MasterDataCache::flush();

        $this->getJson('/api/v1/service-areas')->assertJsonCount(20, 'data');
    }
}
