<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SellerShippingProvider extends Model
{
    use HasFactory;

    protected $table = 'seller_shipping_providers';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'seller_id',
        'provider_id',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
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

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function provider()
    {
        return $this->belongsTo(ShippingProvider::class, 'provider_id');
    }
}
