<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CustomerVehicle */
class CustomerVehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $category = $this->category;
        $model = $this->model;

        return [
            'id' => $this->id,
            'plate_number' => $this->plate_number,
            'nickname' => $this->nickname,
            'year' => $this->year,
            'color' => $this->color,
            'category' => $category === null ? null : [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'vehicle_type' => $category->type?->code,
            ],
            'model' => $model === null ? null : [
                'id' => $model->id,
                'name' => $model->name,
                'brand_name' => $model->brand?->name,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
