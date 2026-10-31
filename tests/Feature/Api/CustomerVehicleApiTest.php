<?php

namespace Tests\Feature\Api;

use App\Models\CustomerVehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use Database\Seeders\MasterVehicleSeeder;

class CustomerVehicleApiTest extends ApiTestCase
{
    private VehicleCategory $matic;

    private VehicleModel $beat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MasterVehicleSeeder::class);
        $this->matic = VehicleCategory::where('code', 'motor_matic')->firstOrFail();
        $this->beat = VehicleModel::where('name', 'BeAT')->firstOrFail();
    }

    public function test_full_crud_cycle_with_plate_normalisation(): void
    {
        $user = $this->makeUser();
        $headers = $this->bearer($this->tokenFor($user));

        $created = $this->postJson('/api/v1/me/vehicles', [
            'vehicle_category_id' => $this->matic->id,
            'vehicle_model_id' => $this->beat->id,
            'plate_number' => 'bk1234abc',
            'year' => 2022,
            'color' => 'Merah',
            'nickname' => 'Si Merah',
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.plate_number', 'BK 1234 ABC')
            ->assertJsonPath('data.category.code', 'motor_matic')
            ->assertJsonPath('data.category.vehicle_type', 'motor')
            ->assertJsonPath('data.model.name', 'BeAT')
            ->assertJsonPath('data.model.brand_name', 'Honda')
            ->json('data');

        $id = $created['id'];

        $this->getJson('/api/v1/me/vehicles', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id);

        $this->getJson("/api/v1/me/vehicles/{$id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.nickname', 'Si Merah');

        $this->putJson("/api/v1/me/vehicles/{$id}", [
            'vehicle_category_id' => $this->matic->id,
            'plate_number' => 'BK 1234 ABC',
            'color' => 'Hitam',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.color', 'Hitam')
            ->assertJsonPath('data.model', null);

        $this->deleteJson("/api/v1/me/vehicles/{$id}", [], $headers)
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertSoftDeleted('customer_vehicles', ['id' => $id]);
        $this->getJson("/api/v1/me/vehicles/{$id}", $headers)->assertNotFound()->assertJsonStructure(['message']);

        // A deleted plate can be registered again.
        $this->postJson('/api/v1/me/vehicles', [
            'vehicle_category_id' => $this->matic->id,
            'plate_number' => 'BK 1234 ABC',
        ], $headers)->assertCreated();
    }

    public function test_validation(): void
    {
        $user = $this->makeUser();
        $headers = $this->bearer($this->tokenFor($user));

        $this->postJson('/api/v1/me/vehicles', [], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['vehicle_category_id', 'plate_number']);

        $this->postJson('/api/v1/me/vehicles', [
            'vehicle_category_id' => 99999,
            'plate_number' => 'bukan plat!!',
            'year' => 1800,
        ], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['vehicle_category_id', 'plate_number', 'year'])
            ->assertJsonPath('errors.plate_number.0', 'Format nomor polisi tidak valid. Contoh: BK 1234 ABC.');
    }

    public function test_duplicate_plate_for_same_user_is_rejected(): void
    {
        $user = $this->makeUser();
        $headers = $this->bearer($this->tokenFor($user));
        $payload = ['vehicle_category_id' => $this->matic->id, 'plate_number' => 'BK 5678 XY'];

        $this->postJson('/api/v1/me/vehicles', $payload, $headers)->assertCreated();
        $this->postJson('/api/v1/me/vehicles', ['plate_number' => 'bk 5678 xy'] + $payload, $headers)
            ->assertUnprocessable()
            ->assertJsonPath('errors.plate_number.0', 'Nomor polisi ini sudah ada di daftar kendaraan Anda.');

        // A different customer may register the same plate (e.g. after buying it).
        $other = $this->makeUser();
        $this->freshAuth();
        $this->postJson('/api/v1/me/vehicles', $payload, $this->bearer($this->tokenFor($other)))->assertCreated();
    }

    public function test_cannot_access_another_customers_vehicle(): void
    {
        $owner = $this->makeUser();
        $vehicle = CustomerVehicle::factory()->create([
            'user_id' => $owner->id,
            'vehicle_category_id' => $this->matic->id,
        ]);

        $intruder = $this->makeUser();
        $headers = $this->bearer($this->tokenFor($intruder));

        $this->getJson("/api/v1/me/vehicles/{$vehicle->id}", $headers)->assertNotFound();
        $this->putJson("/api/v1/me/vehicles/{$vehicle->id}", [
            'vehicle_category_id' => $this->matic->id,
            'plate_number' => 'BK 1 A',
        ], $headers)->assertNotFound();
        $this->deleteJson("/api/v1/me/vehicles/{$vehicle->id}", [], $headers)->assertNotFound();
        $this->getJson('/api/v1/me/vehicles', $headers)->assertOk()->assertJsonCount(0, 'data');

        $this->assertNotSoftDeleted('customer_vehicles', ['id' => $vehicle->id]);
    }

    public function test_plate_normaliser(): void
    {
        $this->assertSame('BK 1234 ABC', CustomerVehicle::normalizePlate('bk-1234-abc'));
        $this->assertSame('B 1 A', CustomerVehicle::normalizePlate(' b 1 a '));
        $this->assertSame('BK 99', CustomerVehicle::normalizePlate('BK99'));
    }
}
