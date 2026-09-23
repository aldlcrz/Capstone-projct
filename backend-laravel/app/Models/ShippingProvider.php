<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingProvider extends Model
{
    use HasFactory;

    protected $table = 'shipping_providers';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'code',
        'logo_path',
        'default_volumetric_divisor',
        'is_active',
        'is_platform_default',
    ];

    protected $casts = [
        'default_volumetric_divisor' => 'integer',
        'is_active' => 'boolean',
        'is_platform_default' => 'boolean',
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

    public function rates()
    {
        return $this->hasMany(ShippingRate::class, 'provider_id');
    }

    public function sellerProviders()
    {
        return $this->hasMany(SellerShippingProvider::class, 'provider_id');
    }
}
