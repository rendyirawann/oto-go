<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One service price for one vehicle category inside a template (rupiah). */
class PriceTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_template_id',
        'module_code',
        'group_name',
        'service_name',
        'vehicle_category_code',
        'price',
        'duration_minutes',
        'is_addon',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'duration_minutes' => 'integer',
            'is_addon' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PriceTemplate::class, 'price_template_id');
    }
}
