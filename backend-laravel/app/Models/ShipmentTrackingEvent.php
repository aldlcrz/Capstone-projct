<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShipmentTrackingEvent extends Model
{
    use HasFactory;

    protected $table = 'shipment_tracking_events';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'order_id',
        'order_shipping_id',
        'courier_code',
        'tracking_number',
        'checkpoint_status',
        'raw_status',
        'location',
        'description',
        'occurred_at',
        'event_hash',
        'raw_payload',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'raw_payload' => 'array',
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

    public function orderShipping()
    {
        return $this->belongsTo(OrderShipping::class, 'order_shipping_id');
    }
}
