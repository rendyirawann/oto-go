<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A vehicle owned by a customer ("Kendaraan Saya"). */
class CustomerVehicle extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'vehicle_model_id',
        'vehicle_category_id',
        'plate_number',
        'year',
        'color',
        'nickname',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    /** Eager loads everything the API resource renders. */
    public function scopeWithDetails(Builder $query): Builder
    {
        return $query->with([
            'category:id,vehicle_type_id,code,name',
            'category.type:id,code',
            'model:id,vehicle_brand_id,name',
            'model.brand:id,name',
        ]);
    }

    /**
     * Normalise an Indonesian plate number: uppercase, single spaces between
     * the region code, the number and the suffix ("bk1234abc" -> "BK 1234 ABC").
     */
    public static function normalizePlate(string $plate): string
    {
        $compact = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plate) ?? '');

        if (preg_match('/^([A-Z]{1,2})(\d{1,4})([A-Z]{0,3})$/', $compact, $m)) {
            return trim($m[1] . ' ' . $m[2] . ' ' . $m[3]);
        }

        return strtoupper(trim(preg_replace('/\s+/', ' ', $plate) ?? ''));
    }
}
