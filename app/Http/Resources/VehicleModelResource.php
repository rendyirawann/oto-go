<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\VehicleModel */
class VehicleModelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand_id' => $this->vehicle_brand_id,
            'brand_name' => $this->whenLoaded('brand', fn () => $this->brand?->name),
            'default_category' => $this->defaultCategory === null ? null : [
                'id' => $this->defaultCategory->id,
                'code' => $this->defaultCategory->code,
                'name' => $this->defaultCategory->name,
            ],
        ];
    }
}
