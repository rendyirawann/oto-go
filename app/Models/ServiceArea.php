<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** City + district (kecamatan) where OtoGo operates. */
class ServiceArea extends Model
{
    use HasFactory;

    protected $fillable = ['city', 'district', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
