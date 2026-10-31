<?php

namespace App\Http\Requests\Api\Vehicle;

use App\Http\Requests\Api\ApiRequest;
use App\Models\CustomerVehicle;
use Illuminate\Validation\Rule;

/** Shared by store (POST) and update (PUT) of "Kendaraan Saya". */
class SaveCustomerVehicleRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->plate_number)) {
            $this->merge(['plate_number' => CustomerVehicle::normalizePlate($this->plate_number)]);
        }
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle');
        $maxYear = (int) now()->format('Y') + 1;

        return [
            'vehicle_category_id' => [
                'required', 'integer',
                Rule::exists('vehicle_categories', 'id')->whereNull('deleted_at'),
            ],
            'vehicle_model_id' => [
                'nullable', 'integer',
                Rule::exists('vehicle_models', 'id')->whereNull('deleted_at'),
            ],
            'plate_number' => [
                'required', 'string', 'max:20',
                // Region code (1-2 letters), number (1-4 digits), optional suffix (1-3 letters).
                'regex:/^[A-Z]{1,2} \d{1,4}( [A-Z]{1,3})?$/',
                Rule::unique('customer_vehicles', 'plate_number')
                    ->where('user_id', $this->user()?->getKey())
                    ->whereNull('deleted_at')
                    ->ignore(is_string($vehicleId) ? $vehicleId : null),
            ],
            'year' => ['nullable', 'integer', 'between:1950,' . $maxYear],
            'color' => ['nullable', 'string', 'max:40'],
            'nickname' => ['nullable', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'plate_number.regex' => 'Format nomor polisi tidak valid. Contoh: BK 1234 ABC.',
            'plate_number.unique' => 'Nomor polisi ini sudah ada di daftar kendaraan Anda.',
        ] + parent::messages();
    }
}
