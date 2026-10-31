<?php

namespace App\Actions\Vehicle;

use App\Models\CustomerVehicle;
use App\Models\User;
use App\Models\VehicleModel;

/** Creates or updates a vehicle in the customer's garage. */
class SaveCustomerVehicle
{
    /**
     * @param  array{vehicle_category_id:int,vehicle_model_id?:int|null,plate_number:string,year?:int|null,color?:string|null,nickname?:string|null}  $data
     */
    public function handle(User $user, array $data, ?CustomerVehicle $vehicle = null): CustomerVehicle
    {
        $vehicle ??= new CustomerVehicle(['user_id' => $user->getKey()]);

        // A model that was picked keeps its brand; the category is the
        // customer's choice (owners can correct it at check-in).
        $modelId = $data['vehicle_model_id'] ?? null;
        if ($modelId !== null && ! VehicleModel::whereKey($modelId)->exists()) {
            $modelId = null;
        }

        $vehicle->fill([
            'vehicle_category_id' => (int) $data['vehicle_category_id'],
            'vehicle_model_id' => $modelId,
            'plate_number' => CustomerVehicle::normalizePlate($data['plate_number']),
            'year' => $data['year'] ?? null,
            'color' => isset($data['color']) ? trim((string) $data['color']) : null,
            'nickname' => isset($data['nickname']) ? trim((string) $data['nickname']) : null,
        ]);
        $vehicle->user_id = $user->getKey();
        $vehicle->save();

        return CustomerVehicle::withDetails()->findOrFail($vehicle->getKey());
    }
}
