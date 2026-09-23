<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingZone extends Model
{
    use HasFactory;

    protected $table = 'shipping_zones';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'code',
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

    public function areas()
    {
        return $this->hasMany(ShippingZoneArea::class, 'zone_id');
    }

    public function originRates()
    {
        return $this->hasMany(ShippingRate::class, 'origin_zone_id');
    }

    public function destinationRates()
    {
        return $this->hasMany(ShippingRate::class, 'destination_zone_id');
    }
}
