<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'totalAmount' => 'decimal:2',
            'shippingAddress' => 'array',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customerId');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'sellerId');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'orderId', 'id');
    }

    public function refunds()
    {
        return $this->hasMany(RefundRequest::class, 'orderId');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'orderId');
    }
}
