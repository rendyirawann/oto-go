<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PriceTemplateItem */
class PriceEstimateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'module' => $this->module_code,
            'group' => $this->group_name,
            'service' => $this->service_name,
            'vehicle_category_code' => $this->vehicle_category_code,
            'price' => (int) $this->price,
            'duration_minutes' => $this->duration_minutes,
            'is_addon' => (bool) $this->is_addon,
        ];
    }
}
