<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderShipping extends Model
{
    use HasFactory;

    protected $table = 'order_shipping';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'provider_id',
        'provider_name',
        'shipping_rate_id',
        'origin_zone_id',
        'origin_zone_name',
        'destination_zone_id',
        'destination_zone_name',
        'actual_weight',
        'volumetric_weight',
        'chargeable_weight',
        'rate_base_snapshot',
        'additional_weight_rate_snapshot',
        'volumetric_divisor_snapshot',
        'shipping_fee',
        'estimated_days_min',
        'estimated_days_max',
        'tracking_number',
        'shipping_status',
    ];

    protected $casts = [
        'actual_weight' => 'float',
        'volumetric_weight' => 'float',
        'chargeable_weight' => 'float',
        'rate_base_snapshot' => 'float',
        'additional_weight_rate_snapshot' => 'float',
        'volumetric_divisor_snapshot' => 'integer',
        'shipping_fee' => 'float',
        'estimated_days_min' => 'integer',
        'estimated_days_max' => 'integer',
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

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function provider()
    {
        return $this->belongsTo(ShippingProvider::class, 'provider_id');
    }

    public function shippingRate()
    {
        return $this->belongsTo(ShippingRate::class, 'shipping_rate_id');
    }
}
