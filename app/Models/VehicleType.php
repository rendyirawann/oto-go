<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Vehicle type: motor or mobil (PLAN.md 4.3). */
class VehicleType extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'sort'];

    public function categories(): HasMany
    {
        return $this->hasMany(VehicleCategory::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(VehicleBrand::class);
    }
}
