<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Pricing category of a vehicle, e.g. motor_matic or car_mpv. */
class VehicleCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['vehicle_type_id', 'code', 'name', 'sort'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }
}
