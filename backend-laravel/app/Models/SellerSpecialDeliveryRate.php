<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SellerSpecialDeliveryRate extends Model
{
    use HasFactory;

    protected $table = 'seller_special_delivery_rates';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'seller_id',
        'municipality_key',
        'municipality_name',
        'surcharge',
        'is_enabled',
    ];

    protected $casts = [
        'surcharge'  => 'float',
        'is_enabled' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
