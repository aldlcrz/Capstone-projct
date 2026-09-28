<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    protected $table = 'refund_requests';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'customer_id',
        'seller_id',
        'reason',
        'message',
        'video_proof',
        'status',
        'seller_comment',
    ];

    public $timestamps = true;

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // Accessors for camelCase compatibility
    public function getOrderIdAttribute()
    {
        return $this->attributes['order_id'] ?? null;
    }

    public function setOrderIdAttribute($value)
    {
        $this->attributes['order_id'] = $value;
    }

    public function getOrderItemIdAttribute()
    {
        return $this->attributes['order_item_id'] ?? null;
    }

    public function setOrderItemIdAttribute($value)
    {
        $this->attributes['order_item_id'] = $value;
    }

    public function getCustomerIdAttribute()
    {
        return $this->attributes['customer_id'] ?? null;
    }

    public function setCustomerIdAttribute($value)
    {
        $this->attributes['customer_id'] = $value;
    }

    public function getSellerIdAttribute()
    {
        return $this->attributes['seller_id'] ?? null;
    }

    public function setSellerIdAttribute($value)
    {
        $this->attributes['seller_id'] = $value;
    }

    public function getVideoProofAttribute()
    {
        return $this->attributes['video_proof'] ?? null;
    }

    public function setVideoProofAttribute($value)
    {
        $this->attributes['video_proof'] = $value;
    }

    public function getSellerCommentAttribute()
    {
        return $this->attributes['seller_comment'] ?? null;
    }

    public function setSellerCommentAttribute($value)
    {
        $this->attributes['seller_comment'] = $value;
    }
}
