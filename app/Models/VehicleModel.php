<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A vehicle model (e.g. Toyota Avanza) with its default pricing category. */
class VehicleModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['vehicle_brand_id', 'name', 'default_category_id'];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'vehicle_brand_id');
    }

    public function defaultCategory(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'default_category_id');
    }
}
