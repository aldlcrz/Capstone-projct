<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'productId');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customerId');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'orderId');
    }
}
