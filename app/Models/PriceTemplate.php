<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Platform price template, e.g. "Tarif Pasar Medan 2026". */
class PriceTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'city', 'disclaimer', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PriceTemplateItem::class);
    }
}
