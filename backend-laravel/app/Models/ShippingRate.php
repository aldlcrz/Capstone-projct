<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingRate extends Model
{
    use HasFactory;

    protected $table = 'shipping_rates';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'provider_id',
        'origin_zone_id',
        'destination_zone_id',
        'min_weight',
        'max_weight',
        'base_rate',
        'additional_weight_rate',
        'volumetric_divisor',
        'estimated_days_min',
        'estimated_days_max',
        'is_active',
    ];

    protected $casts = [
        'min_weight' => 'float',
        'max_weight' => 'float',
        'base_rate' => 'float',
        'additional_weight_rate' => 'float',
        'volumetric_divisor' => 'integer',
        'estimated_days_min' => 'integer',
        'estimated_days_max' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function provider()
    {
        return $this->belongsTo(ShippingProvider::class, 'provider_id');
    }

    public function originZone()
    {
        return $this->belongsTo(ShippingZone::class, 'origin_zone_id');
    }

    public function destinationZone()
    {
        return $this->belongsTo(ShippingZone::class, 'destination_zone_id');
    }
}
