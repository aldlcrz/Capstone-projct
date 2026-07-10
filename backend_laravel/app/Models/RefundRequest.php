<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    use HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class, 'orderId');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'orderItemId');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customerId');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'sellerId');
    }
}
