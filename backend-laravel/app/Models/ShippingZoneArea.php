<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingZoneArea extends Model
{
    use HasFactory;

    protected $table = 'shipping_zone_areas';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'zone_id',
        'postal_code',
        'postal_code_prefix',
        'province',
        'city',
        'barangay',
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

    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'zone_id');
    }
}
